<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Kline extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取行情';

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
        $list = DB::table('Kline')->get();
        
        foreach($list as $item){
            try{
                $result = $this->GetPrice($item->Coin, $item->PriceCoin);
                var_dump($result);
                if($result['status'] == 'ok'){
                    $pirceCny = 0;
                    if(strtolower($item->PriceCoin) == 'usdt'){
                        $pirceCny = $result['tick']['close'] * 7;
                    } else {
                        $priceUsdt = $this->GetPrice($item->PriceCoin, 'usdt');
                        if($priceUsdt['status'] == 'ok'){
                            $pirceCny = $priceUsdt['tick']['close'] * 7;
                            $pirceCny = $result['tick']['close'] * $pirceCny;
                        }
                    }
                    DB::table('Kline')->where('Coin', $item->Coin)->where('PriceCoin', $item->PriceCoin)->update([
                        'Price' => $result['tick']['close'],
                        'PriceCny' => $pirceCny,
                        'Kline' => bcdiv(bcsub($result['tick']['close'], $result['tick']['open'], 10), $result['tick']['open'], 10)
                    ]);
                }
            } catch(\Exception $e){
                continue;
            }
            
        }
    }

    protected function GetPrice($coin, $priceCoin){
        $base = 'https://api.huobi.pro/market/detail?symbol=';
        $param = strtolower($coin.$priceCoin);
        $url = $base.$param;
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'timeout' => 60 * 60
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result, true);
        return $result;
    }

    // protected function Send($url){
    //     $ch = curl_init();
    //     curl_setopt($ch, CURLOPT_URL, $url);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    //     $dom = curl_exec($ch);
    //     curl_close($ch);
    //     return $dom;
    // }
}
