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

    protected $_lvs = null;

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
            //
            $lvs = DB::table('CommunityLevelSetting')->orderBy('Level','desc')->get();
            $this->_lvs = $lvs;
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
        DB::table('Members')->where('Id', $item->Id)->update(['CommunityLevel' => 0]);
        if($item->HasInv != 1) return ;
        $zt = Member::where('ParentId', $item->Id)->where('HasInv', 1)->count();
        foreach($this->_lvs as $nextLv){
            //伞下业绩
            if(bccomp($item->TeamAchievement, $nextLv->Achive) < 0) continue;
            //有效直推
            if($zt < $nextLv->InviteNumber) continue;
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
                    continue ;
                }
                //是否购买预约等级的商品
                $products = DB::table('Products')->where('NeedLevel', $nextLv->PlanLevel)->get()->toArray();
                $pids = array_column($products, 'Id');
                $has = DB::table('MemberProducts')
                    ->where('MemberId', $item->Id)
                    ->whereIn('State', [1,2])
                    ->whereIn('ProductId', $pids)
                    ->first();
                if(empty($has)) continue ;
                //可以升级啦~
                DB::table('Members')->where('Id', $item->Id)->update(['CommunityLevel' => $nextLv->Level]);
                //升级完成
                return ;
            }
        }
    }

    //获取
    public function GetRoot(){

    }

}
