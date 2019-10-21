<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Member;

class LevelUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'level:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '社区等级升级';

    protected $_plan_map = [];

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
        $today = intval(date('Ymd'));
        DB::beginTransaction();
        try{
            $plan = DB::table('PlanLevel')->get()->toArray();
            $planMap = array_column($plan, 'ProductId', 'Level');
            foreach($planMap as $k => $v){
                $planMap[$k] = json_decode($v);
            }
            $this->_plan_map = $planMap;
            //从最后注册的用户开始升级
            $members = Member::where('LevelUpdateDate', '<>', $today)->orderBy('Id','desc')->paginate(1000);
            foreach($members as $item){
                //更新升级时间
                DB::table('Members')->where('Id', $item->Id)->update(['LevelUpdateDate' => $today]);
                $this->Update($item);
            }
            DB::commit();
        }catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
            exit;
        }
    }

    public function Update($item){
        //只有预约等级为2级以上的社区等级才能升级
        //if($item->PlanLevel < 2) return ;
        //社区等级为4级(台长)的时候不用继续升级
        if($item->CommunityLevel >= 4) return ;
        //取下一级配置
        $nextLv = DB::table('CommunityLevelSetting')->where('Level','>', $item->CommunityLevel)->first();
        if(empty($nextLv)) return ;
        //伞下业绩
        if(bccomp($item->TeamAchievement, $nextLv->Achive) < 0) return ;
        //有效直推
        $zt = Member::where('ParentId', $item->Id)->where('HasInv', 1)->count();
        if($zt < $nextLv->InviteNumber) return ;
        //5代内至少两条链出现要求等级
        //5代内一共出现要求等级3次以上
        $subMember = Member::where('ParentId', $item->Id)->get();
        $chain = 0; //满足条件的链
        $achieve = 0; //满足等级的人数
        foreach($subMember as $sitem){
            //获取一级代理的四代内用户
            $fourth = $sitem->FourthRoot;
            $fourth = explode('|', $fourth);
            if(!is_array($fourth)) continue;
            $fifth = $fourth;
            $fifth[] = $sitem->Id;
            //五代内用户
            $fifth = array_filter($fifth);
            $reach = Member::whereIn('Id', $fifth)->where('CommunityLevel', $nextLv->HasLevel)->count();
            if($reach > 0) $chain++;
            $achieve += $reach;
            //如果达到条件，直接跳出
            if($chain >= 2 && $achieve >= 3) break;
        }
        //判断升级条件
        if($chain >= 2 && $achieve >= 3){
            //没达到预约等级则更新为当前所需等级 社区等级不提升
            if($item->PlanLevel < $nextLv->PlanLevel){
                DB::table('Members')->where('Id', $item->Id)->update([
                    'PlanLevel' => $nextLv->PlanLevel
                ]);
                return ;
            }
            //是否购买预约等级的商品
            if(!isset($this->_plan_map[$nextLv->PlanLevel])) return;
            $has = DB::table('MemberProducts')
                ->where('MemberId', $item->MemberId)
                ->whereIn('State', [1,2])
                ->whereIn('ProductId', $this->_plan_map[$nextLv->PlanLevel])
                ->first();
            if(empty($has)) return ;
            //可以升级啦~
            DB::table('Members')->where('Id', $item->Id)->update(['CommunityLevel' => $nextLv->Level]);
            //继续升级知道不能升级为止
            $member = Member::where('Id', $item->Id)->first();
            $this->Update($member);
        }
    }

    //获取
    public function GetRoot(){

    }

}
