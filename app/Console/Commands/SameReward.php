<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Members;

class SameReward extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'same:reward';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '平级奖励';

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
        DB::beginTransaction();
        try{
            $reward = DB::table('RewardRecord')->where('Type', 3)->where('IsSameReward', 0)->paginate(1000);
            $now = time();
            foreach($reward as $item){
                DB::table('RewardRecord')->where('Id', $item->Id)->update(['IsSameReward' => 1]);
                $member = Members::where('Id', $item->MemberId)->first();
                if(empty($member)) continue;
                $root = $this->GetRoot($member->Root);
                $pMembers = Members::whereIn('Id', $root)->orderBy('Id','desc')->get();
                foreach($pMembers as $pMember){
                    //当前没有投资则跳过
                    if($pMember->HasInv != 1) continue;
                    //中间来个大的直接滚蛋
                    if($pMember->CommunityLevel > $member->CommunityLevel) break;
                    if($pMember->CommunityLevel == $member->CommunityLevel){
                        //找到了! 平级奖拿社区奖励的10%
                        $reward = bcmul($item->Number, 0.1, 10);
                        DB::table('Members')->where('Id', $pMember->Id)->increment('Balance', $reward);
                        DB::table('RewardRecord')->insert([
                            'MemberId' => $pMember->Id,
                            'Number' => $reward,
                            'Type' => 5,
                            'AddTime' => $now
                        ]);
                        //弄完了就跑了,只给一个名额
                        break;
                    }
                }
            }
            DB::commit();
        } catch(\Exception $e){
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
        return array_reverse($root);
    }
}
