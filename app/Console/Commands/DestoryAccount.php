<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MembersModel as Members;

class DestoryAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'destory:account';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '销毁账号';

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
        //若30天内未复投，则，账号销毁，且将该账号从邀请链中移除。
        $nTime = strtotime(date('Y-m-d')) - 30 * 86400;
        DB::beginTransaction();
        try{
            $now = time();
            $members = Members::where('Achievement','>',0)->get();
            foreach($members as $member){
                $id = $member->Id;
                //如果30天内未报单
                $has = DB::table('MemberProducts')->where('MemberId', $id)->where('PayTime','>', $nTime)->first();
                if(!empty($has)) continue;
                //从邀请链里面删除
                $zMembers = Members::where('ParentId', '<>', $id)->where('Root','like',"%,{$id},%")->get();
                //中间删除
                foreach($zMembers as $zMember){
                    $root = $this->GetRoot($zMember->Root, $id);
                    $root = implode(',', $root);
                    $root = ','.$root.',';
                    DB::table('Members')->where('Id', $zMember->Id)->update(['Root' => $root]);
                }
                //一级代理删除
                $zMemberSts = Members::where('ParentId', $id)->get();
                foreach($zMemberSts as $zMemberSt){
                    $root = $this->GetRootSt($zMemberSt->Root, $id);
                    $parentId = $root[count($root) - 1];
                    $root = implode(',', $root);
                    $root = ','.$root.',';
                    DB::table('Members')->where('Id', $zMemberSt->Id)->update(['Root' => $root, 'ParentId' => $parentId]);
                }
                //删除用户
                DB::table('Members')->where('Id', $id)->delete();
                //记录
                DB::table('MemberDestory')->insert([
                    'MemberId' => $id,
                    'Root' => $member->Root,
                    'Email' => $member->Email,
                    'Phone' => $member->Phone,
                    'AddTime' => $now
                ]);
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
    }

    public function GetRoot($root, $id){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        $res = [];
        foreach($root as $item){
            if($id == $item) continue;
            $res[] = $item;
        }
        return $res;
    }

    public function GetRootSt($root, $id){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        unset($root[count($root) - 1]);
        return $root;
    }
    
}
