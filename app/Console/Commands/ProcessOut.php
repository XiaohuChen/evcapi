<?php

namespace App\Console\Commands;

use App\Exceptions\ArException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:out';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '出局';

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
        //把所有当前没有报单的用户改为无效用户
        DB::beginTransaction();
        try{
            DB::table('Members')->where('WaitOut', 1)->update([
                'HasInv' => 0,
                'WaitOut' => 0
            ]);
        } catch(\Exception $e){
            DB::rollBack();
            var_dump($e->getMessage());
        }
    }
}
