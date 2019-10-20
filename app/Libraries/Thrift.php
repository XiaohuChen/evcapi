<?php
namespace App\Libraries;

use App\Exceptions\ArException;
use App\Libraries\CoinService\CoinServiceClient;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\TBufferedTransport;

class Thrift
{
    protected $transport = null;

    protected $_client = null;

    public function __construct()
    {
        $ip = env('THRIFT_IP', null);
        if(empty($ip)) throw new ArException(ArException::SELF_ERROR,'THRIFT IP NOT FOUND');
        $port = env('THRIFT_PORT', null);
        if(empty($port)) throw new ArException(ArException::SELF_ERROR,'THRIFT PORT NOT FOUND');
        $socket = new TSocket($ip, $port);
        $socket->setSendTimeout(60000);
        $socket->setRecvTimeout(60000);
        $this->transport = new TBufferedTransport($socket, 1024, 1024);
        $protocol = new TBinaryProtocol($this->transport);
        //币种服务
        $this->_client = new CoinServiceClient($protocol);
        $this->transport->open();
    }

    public function GetClient(){
        return $this->_client;
    }
}
