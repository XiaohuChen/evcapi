<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Members;

class CommunityReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'community:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '社区奖励';

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
        //需要升级完成过后才能计算社区奖励
        $today = intval(date('Ymd'));
        $count = Members::where('LevelUpdateDate', '<>', $today)->first();
        if(!empty($count)) exit("升级完成后再计算社区奖励");
        $zDay = strtotime(date('Y-m-d'));
        DB::beginTransaction();
        try{
            $output = DB::table('OutputLog')->where('IsCommunityReward',0)->where('AddTime', '>', $zDay)->orderBy('Id','asc')->paginate(1000);
            //获取等级配置
            $lSet = DB::table('CommunityLevelSetting')->get()->toArray();
            $lMap = array_column($lSet, 'Ratio', 'Level');
            $now = time();
            foreach($output as $item){
                $member = Members::where('Id', $item->MemberId)->first();
                $root = $this->GetRoot($member->Root);
                //出去上级代理，按注册的先后顺序
                $pMembers = Members::whereIn('Id', $root)
                    ->where('HasInv', 1) //当前没有有效投资的无法获取奖励
                    ->where('IsForbidden', 0) //封禁的账号无法获取奖励
                    ->where('CommunityLevel', '>', 0) //没有社区等级则没有奖励
                    ->orderBy('Id','desc') //注册的先后顺序
                    ->get();
                $currentRatio = 0; //当前奖励比例
                foreach($pMembers as $pMember){
                    //没有对应奖励跳过
                    if(!isset($lMap[$pMember->CommunityLevel])) continue;
                    //扣掉级差 扣掉后没有奖励则跳过
                    $ratio = bcsub($lMap[$pMember->CommunityLevel], $currentRatio, 10);
                    if(bccomp($ratio, 0, 10) <= 0) continue;
                    //算出奖励
                    $reward = bcmul($item->Number, $ratio, 10);
                    //奖励加到收益余额
                    DB::table('Members')->where('Id', $pMember->Id)->increment('Balance', $reward);
                    //记录收益
                    DB::table('RewardRecord')->insert([
                        'MemberId' => $pMember->Id,
                        'Number' => $reward,
                        'Type' => 3,
                        'AddTime' => $now
                    ]);
                }
                //修改状态
                DB::table('OutputLog')->where('Id', $item->Id)->update(['IsCommunityReward' => 1]);
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
        }
    }

    public function GetRoot($root){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        return array_reverse($root);
    }
}
