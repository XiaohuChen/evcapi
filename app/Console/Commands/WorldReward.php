<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Members;

class WorldReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'world:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '全球分红';

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
        $today = strtotime(date('Y-m-d'));
        $now = time();
        DB::beginTransaction();
        try{
            //能拿到全球分红的人
            $lv = DB::table('CommunityLevelSetting')->where('World', 1)->get()->toArray();
            $lv = array_column($lv, 'Level');
            if(empty($lv)) throw new \Exception('没有等级能拿到全球分红');
            //当前没有投资不能拿全球分红
            $count = Members::whereIn('CommunityLevel', $lv)->where('HasInv', 1)->count();
            //按人头平分当日静态奖励、邀请奖励、社区奖励、平级奖励之和的5%
            //Type 1静态奖励 2邀请奖励 3社区奖励 4封号清空收益 5平级奖励
            $number = DB::table('RewardRecord')->whereIn('Type',[1,2,3,5])->where('AddTime', '>', $today)->sum('Number');
            $setting = DB::table('RatioSetting')->first();
            if(empty($setting)) throw new \Exception('奖励比例未设置');
            $reward = bcmul($number, $setting->WorldRatio, 10);
            //每个人分得的数量
            $single = bcdiv($reward, $count, 10);
            $members = Members::whereIn('CommunityLevel', $lv)->get();
            foreach($members as $member){
                DB::table('Members')->where('Id', $member->Id)->increment('Balance', $single);
                DB::table('RewardRecord')->insert([
                    'MemberId' => $member->Id,
                    'Number' => $single,
                    'Type' => 6,
                    'AddTime' => $now
                ]);
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
            exit;
        }
    }
}
