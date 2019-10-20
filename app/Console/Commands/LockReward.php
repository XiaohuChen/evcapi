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
        //
        $time = strtotime(date('Y-m-d')) -  86400;
        $today = intval(date('Ymd'));
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
                //加入收益余额
                DB::table('Members')->where('Id', $item->MemberId)->increment('Balance', $static);
                //加入产出记录
                DB::table('OutputLog')->insert([
                    'MemberId' => $item->MemberId,
                    'Number' => $static,
                    'ProductId' => $item->ProductId,
                    'RewardDate' => $today,
                    'AddTime' => $now
                ]);
                //加入奖励记录
                DB::table('RewardRecord')->insert([
                    'MemberId' => $item->MemberId,
                    'Number' => $static,
                    'Type' => 1,
                    'AddTime' => $now
                ]);
                //如果剩余最后一天，则修改状态
                if($item->SurplusDay == 1){
                    DB::table('MemberProducts')->where('Id', $item->Id)->update(['State' => 2,'ExpireTime' => time()]);
                    //退还本金
                    DB::table('MemberCoin')
                        ->where('MemberId', $item->MemberId)
                        ->where('CoinId', $evc->Id)
                        ->increment('Money', $item->NumberEvc);
                    $this->AddLog($item->MemberId, $item->NumberEvc, $evc, 'unlock_return_capital');
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
                    DB::table('Members')->where('Id', $item->MemberId)->update(['HasInv' => 0]);
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
                }
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
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
