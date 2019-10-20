<?php

namespace App\Exceptions;

//自定义异常类，方便全局异常处理
class ArException extends \Exception
{
    //未知
    const NET_WORK_ERROR = 99999;
    //公共
    const UNKONW = 10000;
    const NOT_DATA = 10001;
    const PARAM_ERROR = 10002;
    const SELF_ERROR = 10003;
    const ACCOUNT_NOT_FOUND = 10004;
    const PAY_PASS_ERROR = 10005;

    //Token
    const AUTH_ERROR = 20001;
    const TOKEN_ERROR = 20003;
    const TOKEN_EXPIRE = 20004;
    const NO_LOGIN = 20005;
    const ILLEGAL_LOGIN = 20006;

    //Member
    const USER_NOT_FOUND = 30001;
    const USER_BE_BAN = 30002;

    //Coin
    const COIN_NOT_FOUND = 40001;
    const HAS_NO_COIN = 40002;
    const COIN_NOT_ENOUGH = 40003;

    protected $map = [
        //Common
        self::UNKONW => '未知错误',
        self::NOT_DATA => '暂无数据',
        self::PARAM_ERROR => '参数错误',
        self::SELF_ERROR => '%s',
        self::PAY_PASS_ERROR => '交易密码错误',
        //System
        self::NET_WORK_ERROR => '网络繁忙，请稍后再试',
        //Auth
        self::AUTH_ERROR => '登录凭证无效',
        self::TOKEN_ERROR => '登录凭证已失效',
        self::TOKEN_EXPIRE => '登录凭证过期,请重新登录',
        self::NO_LOGIN => '请先登录',
        self::ILLEGAL_LOGIN => '登录信息已失效,请重新登录', //非法token 
        self::USER_NOT_FOUND => '用户不存在或已注销',
        self::USER_BE_BAN => '用户已被禁用',
        self::ACCOUNT_NOT_FOUND => '账号或密码错误',
        //Coin
        self::COIN_NOT_FOUND => '未找到该币种',
        self::COIN_NOT_ENOUGH => '余额不足',
        
    ];

    /**
     * ArException constructor.
     * @param int|10000 $code
     * @param string|string $arData
     */
    public function __construct(int $code = 10000, string $arData = '')
    {
        $this->message = sprintf($this->map[$code], $arData);
        $this->code = $code;
    }

}