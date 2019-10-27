<?php

namespace App\Libraries;

include_once dirname(__FILE__).'/Aliyun/aliyun-php-sdk-core/Config.php';

use App\Exceptions\ArException;
use DefaultAcsClient;
use \DefaultProfile;
use Dm\Request\V20151123\SingleSendMailRequest;

class SendEmail{
    
    protected $_code = '';

    protected $_email = '';

    public function __construct($code, $email){
        $this->_code = $code;
        $this->_email = $email;
    }

    public function Send(){
        $iClientProfile = DefaultProfile::getProfile("cn-hangzhou", "LTAI4FoZvf7Qsdh68aGQ7d2X", "NFpYrBLFlRa3SxkFxDJTLLFZbt2CLr");
        $client = new DefaultAcsClient($iClientProfile);
        $request = new SingleSendMailRequest(); 
        $request->setAccountName("mail@dev.evcblock.tech");
        $request->setFromAlias("EVC官方");
        $request->setAddressType(1);
        $request->setTagName("EVC");
        $request->setReplyToAddress("true");
        $request->setToAddress($this->_email);
        $request->setSubject("验证码");
        $request->setHtmlBody("<p>尊敬的用户，您好！</p ><p>本次验证码为:</p ><h3>{$this->_code}</h3>");
        try {
            $response = $client->getAcsResponse($request);
            return true;
        } catch (\Exception $e) {
            if(env('APP_DEBUG'))
                throw new ArException(ArException::SELF_ERROR, $e->getMessage());
            
            throw new ArException(ArException::SELF_ERROR, '验证码发送失败');
        }
    }

}
