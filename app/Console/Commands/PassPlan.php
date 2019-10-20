<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MemberProductModel as MemberProduct;
use Illuminate\Support\Facades\DB;

class PassPlan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plan:pass';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '放行预约单';

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
        //每日放行数量
        $setting = DB::table('SystemSetting')->first();
        if(empty($setting)) exit("系统放行数量设置有误");
        DB::beginTransaction();
        try{
            $time = time();
            $today = strtotime(date('Y-m-d'));
            $totalPass = MemberProduct::where('State', 3)->where('PassTime', '>', $today)->sum('Number');  //当日当前放行数量
            //未放行预约单 先进先放行
            $notPass = MemberProduct::where('State', 0)->orderBy('Id','asc')->paginate(1000); //每次最多放行1000单
            foreach($notPass as $item){
                $alreadyNum = bcadd($item->Number, $totalPass, 10);
                if(bccomp($alreadyNum, $setting->PassNumber, 10) > 0) break; //放行数量已达上限
                //进行放行
                DB::table('MemberProducts')->where('Id', $item->Id)->update([
                    'State' => 3,
                    'PassTime' => $time
                ]);
                //叠加放行数量
                $totalPass = bcadd($item->Number, $totalPass, 10);
            }
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
            var_dump($e->getLine());
        }
    }
}
