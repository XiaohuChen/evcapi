<?php


namespace App\Services;

use App\Exceptions\ArException;
use App\Libraries\SendEmail;
use Illuminate\Support\Facades\Redis;
use App\Models\MembersModel as Members;
use Illuminate\Support\Facades\DB;

class SmsService extends Service
{

    /**
     * @method 绑定手机
     * 
     */
    public function BindPhoneCode($phone){
        if(!isMobile($phone))
            throw new ArException(ArException::SELF_ERROR,'手机号错误');

        $auth = Redis::hget('BindPhoneCode', $phone);
        if(!empty($auth)){
            $auth = json_decode($auth, true);
            if(is_array($auth)){
                if((time() - $auth['SendTime']) < 60) throw new ArException(ArException::SELF_ERROR,'每分钟只能发送一次验证码');
            }
        }
        //是否有用户
        $has = Members::where('Phone', $phone)->first();
        if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'该手机号已绑定其他账号');
        //发送验证码
        $code = rand(100000,999999);
        $this->JuHeSms($phone, $code,self::$_forget_tpl);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('BindPhoneCode', $phone, json_encode($auth));
    }

    /**
     * @method 忘记交易密码
     * @param $phone 手机号
     */
    public function ModifyPayPassCode($phone){
        preg_match_all("/^1[345789]\d{9}$/", $phone, $match);
        if(empty($match[0])) throw new ArException(ArException::SELF_ERROR,'手机号错误');
        $auth = Redis::hget('ModifyPayPass',$phone);
        if(!empty($auth)){
            $auth = json_decode($auth, true);
            if(is_array($auth)){
                if((time() - $auth['SendTime']) < 60) throw new ArException(ArException::SELF_ERROR,'每分钟只能发送一次验证码');
            }
        }
        //是否有用户
        $has = Members::where('Phone', $phone)->first();
        if(empty($has)) throw new ArException(ArException::SELF_ERROR,'该手机号没有注册');
        //发送验证码
        $code = rand(100000,999999);
        $this->JuHeSms($phone, $code,self::$_forget_tpl);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('ModifyPayPass', $phone, json_encode($auth));
    }

    /**
     * @method 忘记交易密码
     * @param $phone 手机号
     */
    public function WithdrawCode($id){
        $member = Members::where('Id', $id)->first();
        if(empty($member->Phone))
            throw new ArException(ArException::SELF_ERROR,'你还没绑定手机号');
        $phone = $member->Phone;
        $auth = Redis::hget('WithdrawCode',$phone);
        if(!empty($auth)){
            $auth = json_decode($auth, true);
            if(is_array($auth)){
                if((time() - $auth['SendTime']) < 60) throw new ArException(ArException::SELF_ERROR,'每分钟只能发送一次验证码');
            }
        }
        //发送验证码
        $code = rand(100000,999999);
        $this->JuHeSms($phone, $code,self::$_forget_tpl);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('WithdrawCode', $phone, json_encode($auth));
    }

    /**
     * @method 忘记密码
     * @param $phone 手机号
     */
    public function ModifyPassCode($phone){
        preg_match_all("/^1[345789]\d{9}$/", $phone, $match);
        if(empty($match[0])) throw new ArException(ArException::SELF_ERROR,'手机号错误');
        $auth = Redis::hget('ModifyPass',$phone);
        if(!empty($auth)){
            $auth = json_decode($auth, true);
            if(is_array($auth)){
                if((time() - $auth['SendTime']) < 60) throw new ArException(ArException::SELF_ERROR,'每分钟只能发送一次验证码');
            }
        }
        $has = Members::where('Phone', $phone)->first();
        if(empty($has)) throw new ArException(ArException::SELF_ERROR,'该手机号没有注册');
        //发送验证码
        $code = rand(100000,999999);
        $this->JuHeSms($phone, $code,self::$_forget_tpl);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('ModifyPass', $phone, json_encode($auth));
    }

    /**
     * @method 注册
     * @param $phone 手机号
     */
    public function RegisterCode($phone){
        preg_match_all("/^1[345789]\d{9}$/", $phone, $match);
        if(empty($match[0])) throw new ArException(ArException::SELF_ERROR,'手机号错误');
        $auth = Redis::hget('AuthCode',$phone);
        if(!empty($auth)){
            $auth = json_decode($auth, true);
            if(is_array($auth)){
                if((time() - $auth['SendTime']) < 60) throw new ArException(ArException::SELF_ERROR,'每分钟只能发送一次验证码');
            }
        }
        $has = Members::where('Phone', $phone)->first();
        if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'该手机号已注册');
        //发送验证码
        $code = rand(100000,999999);
        $this->JuHeSms($phone, $code,self::$_reg_tpl);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('AuthCode', $phone, json_encode($auth));
    }

    public function SendCode($email, $opt = ''){
        if(empty($email)) throw new ArException(ArException::SELF_ERROR, '请输入邮箱');
        if(empty($opt)) throw new ArException(ArException::PARAM_ERROR);
        $code = mt_rand(100000, 999999);
        $client = new SendEmail($code, $email);
        $client->Send();
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset($opt, $email, json_encode($auth));
    }

    public function VerifyReg($email){
        $has = DB::table('Members')->where('Email', $email)->first();
        if(empty($has)) throw new ArException(ArException::SELF_ERROR,'该邮箱未注册');
    }

}