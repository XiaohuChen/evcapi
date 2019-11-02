<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Members;
use App\Models\ProductModel as Product;
use App\Models\MemberProductModel as MemberProduct;

class InviteReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invite:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '邀请奖励';

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
        $today = intval(date('Ymd'));
        //获取奖励配置
        $setting = DB::table('InviteRewardSetting')->get()->toArray();
        if(empty($setting)) exit('邀请奖励比例未配置');
        $setting = array_column($setting, 'Ratio', 'ProxyNumber');
        DB::beginTransaction();
        try{
            $statics = DB::table('OutputLog')->where('IsStatic', 1)->where('RewardDate', $today)->where('IsRewardInvite', 0)->paginate(1500);
            $now = time();
            foreach($statics as $item){
                //下级购买的规格
                $prodcut = Product::where('Id', $item->ProductId)->first();
                $lNum = $prodcut->Number;
                //获取Root
                $member = Members::where('Id', $item->MemberId)->first();
                if(empty($member)) continue;
                $root = $this->GetRoot($member->Root);
                foreach($root as $key => $pid){
                    $proxy = $key + 1;
                    //违约封禁的账号没有收益啦!
                    $pMember = Members::where('Id', $pid)->where('IsForbidden', 0)->first();
                    if(empty($pMember)) continue;
                    //是否有进行中的报单 没有不能拿奖励
                    if($pMember->HasInv != 1) continue;
                    //邀请人数 邀请几个有效拿几代
                    $invNum = Members::where('ParentId', $pid)->where('HasInv', 1)->count();
                    if($invNum < $proxy) continue;
                    //如果当前下级的产品包规格大于我的产品包规格，则把我当天产出的数量作为邀请奖励计算的基数来计算邀请收益。
                    $pProduct = MemberProduct::where('MemberId', $pid)->where('State', 1)->first();
                    //计算奖励
                    $reward = 0;
                    if(!isset($setting[$proxy])) continue;
                    $ratio = $setting[$proxy];
                    if(!empty($pProduct) && bccomp($lNum, $pProduct->Number, 10) > 0){
                        $mStatic = bcmul($pProduct->Ratio, $pProduct->Number, 10);
                        $reward = bcmul($mStatic, $ratio, 10);
                    } else {
                        $reward = bcmul($ratio, $item->Number, 10);
                    }
                    //增加收益余额
                    DB::table('Members')->where('Id', $pMember->Id)->increment('Balance', $reward);
                    //记录收益
                    DB::table('RewardRecord')->insert([
                        'MemberId' => $pMember->Id,
                        'Number' => $reward,
                        'Type' => 2,
                        'AddTime' => $now
                    ]);
                }
                DB::table('OutputLog')->where('Id', $item->Id)->update(['IsRewardInvite' => 1]);
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
            exit;
        }
    }

    public function GetRoot($root){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        $root = array_slice($root, -10);
        return array_reverse($root);
    }

}
