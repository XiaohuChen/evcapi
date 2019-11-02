<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MemberProductModel as MemberProduct;
use App\Models\FinancingMoldModel as FinancingMold;
use App\Models\MemberCoinModel as MemberCoin;
use App\Models\CoinModel;
use App\Models\MembersModel;

class LockReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lock:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '静态奖励';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //升级升完了才能执行静态奖励
        $today = intval(date('Ymd'));
        $has = DB::table('Members')->where('LevelUpdateDate', '<>', $today)->first();
        if(!empty($has)) exit('升级完成后再执行静态收益');
        $time = strtotime(date('Y-m-d')) -  86400;
        $evc = CoinModel::where('EnName','EVC')->first();
        if(empty($evc)) exit("币种表没有EVC");
        $usdt = CoinModel::where('EnName','USDT')->first();
        if(empty($usdt)) exit("币种表没有USDT");
        DB::beginTransaction();
        try{
            $mps = MemberProduct::where('State', 1)
                ->where('PayTime', '<', $time)
                ->where('HandleDate', '<', $today)
                ->where('SurplusDay', '>', 0)
                ->paginate(1000);
            $now = time();
            foreach($mps as $item){
                //更新锁仓信息
                DB::table('MemberProducts')->where('Id', $item->Id)->update([
                    'SurplusDay' => DB::raw('SurplusDay-1'),
                    'HandleDate' => $today
                ]);
                $member = MembersModel::where('Id', $item->MemberId)->where('IsForbidden', 0)->first();
                if(empty($member)) continue;
                //静态奖励
                $static = bcmul($item->Number, $item->Ratio, 10);
                //加入待发放余额
                $process = DB::table('ProcessSend')->where('MemberProductId', $item->Id)->first();
                if(empty($process)){
                    DB::table('ProcessSend')->insert([
                        'MemberProductId' => $item->Id,
                        'Number' => $static,
                    ]);
                } else {
                    DB::table('ProcessSend')->where('MemberProductId', $item->Id)->increment('Number', $static);
                }
                //如果剩余最后一天
                if($item->SurplusDay == 1){
                    //加入产出记录
                    DB::table('OutputLog')->insert([
                        'MemberId' => $item->MemberId,
                        'Number' => bcmul($static, 10),
                        'ProductId' => $item->ProductId,
                        'RewardDate' => $today,
                        'AddTime' => $now,
                        'MemberProductId' => $item->Id
                    ]);
                    //加入奖励记录
                    DB::table('RewardRecord')->insert([
                        'MemberId' => $item->MemberId,
                        'Number' => bcmul($static, 10),
                        'Type' => 1,
                        'AddTime' => $now
                    ]);
                    //把收益发给用户
                    $process = DB::table('ProcessSend')->where('MemberProductId', $item->Id)->first();
                    if($process->IsSend != 1){
                        DB::table('Members')->where('Id', $item->MemberId)->increment('Balance', $process->Number);
                        DB::table('ProcessSend')->where('MemberProductId', $item->Id)->update(['IsSend' => 1]);
                    }
                    DB::table('OutputLog')->where('MemberProductId', $item->Id)->update(['IsStatic' => 1]);
                    //，则修改状态
                    DB::table('MemberProducts')->where('Id', $item->Id)->update(['State' => 2,'ExpireTime' => time()]);
                    //退还本金
                    DB::table('MemberCoin')
                        ->where('MemberId', $item->MemberId)
                        ->where('CoinId', $usdt->Id)
                        ->increment('Money', $item->Number);
                    $this->AddLog($item->MemberId, $item->Number, $usdt, 'unlock_return_capital');
                    //退还定金 定金在锁定余额里面，转到余额里面
                    DB::table('MemberCoin')
                        ->where('MemberId', $item->MemberId)
                        ->where('CoinId', $usdt->Id)
                        ->update([
                            'Forzen' => DB::raw('Forzen - 10'),
                            'Money' => DB::raw('Money + 10')
                        ]);
                    $this->AddLog($item->MemberId, 10, $usdt, 'unlock_return_first_money');
                    //用户投资状态修改
                    DB::table('Members')->where('Id', $item->MemberId)->update(['WaitOut' => 1]);
                    //检查升级
                    $member = MembersModel::where('Id', $item->MemberId)->first();
                    if(empty($member)) throw new \Exception('用户数据已出错!!'.$item->Id);
                    $nextLv = DB::table('PlanLevel')->where('Level', '>', $member->PlanLevel)->first();
                    if(!empty($nextLv)){
                        $pids = json_decode($nextLv->ProductId,true);
                        //56天以内出局三次以上，就可以升级
                        $iTime = strtotime(date('Y-m-d')) - 86400 * 56;
                        //十天内出局的次数
                        $hasICountinue = MemberProduct::where('MemberId', $item->MemberId)
                            ->whereIn('ProductId', $pids)
                            ->where('ExpireTime', '>=', $iTime)
                            ->where('State', 2)
                            ->count();
                        if($hasICountinue >= 3){
                            DB::table('Members')->where('Id', $item->MemberId)->update(['PlanLevel' => $nextLv->Level]);
                        }
                    }
                    $root = $this->GetRoot($member->Root);
                    DB::table('Members')->whereIn('Id', $root)->decrement('TeamAchievement', $item->Number);
                    DB::table('Members')->where('Id', $item->MemberId)->decrement('Achievement', $item->Number);
                }
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
    }

    protected function GetRoot($root){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        return array_reverse($root);
    }

    //加日志
    protected function AddLog(int $uid, $money, CoinModel $coin, string $mold){
        $sort = $uid % 20;
        if($sort < 10) $sort = '0'.$sort;
        $table = 'FinancingList_'.$sort;
        $fina = FinancingMold::where('call_index', $mold)->first();
        if(empty($fina)) return ;
        $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
        $data = [
            'MemberId' => $uid,
            'Money' => $money,
            'CoinId' => $coin->Id,
            'CoinName' => $coin->EnName,
            'Mold' => $fina->id,
            'MoldTitle' => $fina->title,
            'Remark' => $fina->title,
            'AddTime' => time(),
            'Balance' => $memberCoin->Money
        ];
        DB::table($table)->insert($data);
    }
}
