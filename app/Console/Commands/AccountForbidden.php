<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\MemberProductModel as MemberProduct;
use App\Models\MembersModel as Member;

class AccountForbidden extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:forbidden';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '封禁违约账号';

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
        //对放行当天18点之前没有进行报单的账号进行封禁
        $today = strtotime(date('Y-m-0'));
        $time = strtotime(date('Y-m-0')) + 18 * 3600;
        if(time() < $time) exit("六点之后再进行封号");
        DB::beginTransaction();
        try{
            $list = MemberProduct::where('PassTime', '>', $today)->where('State', 3)->paginate(1000);
            $now = time();
            foreach($list as $item){
                //禁封后此账号所有收益停止，并清空收益账户
                //如需解封须支付50USDT的解封费用，解封后收益正常产生，且保证金不退
                $member = Member::where('Id', $item->MemberId)->first();
                if(empty($member)) continue;
                DB::table('Members')->where('Id', $item->MemberId)->update([
                    'IsForbidden' => 1,
                    'Balance' => 0
                ]);
                //收支记录
                DB::table('RewardRecord')->insert([
                    'MemberId' => $item->MemberId,
                    'Number' => -$member->Balance,
                    'Type' => 4,
                    'AddTime' => $now
                ]);
                DB::table('MemberProducts')->where('Id', $item->Id)->update([
                    'State' => 4
                ]);
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
    }
}
