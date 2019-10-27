<?php
namespace App\Services;

use App\Exceptions\ArException;
use App\Models\MembersModel as Members;
use App\Models\CoinModel as Coin;
use App\Models\FinancingMoldModel as FinancingMold;
use Illuminate\Support\Facades\DB;
use App\Models\MemberCoinModel as MemberCoin;
use zgldh\QiniuStorage\QiniuStorage;
use Firebase\JWT\JWT;
use App\Libraries\Verify;

class Service
{
    //引入验证类
    use Verify;

    protected static $key = 'sblw-3hn8-sqoy19sblw-3hn';

    protected static $juhe_key = '5ca68e2f11164cc54e5a5a037d0b9d0b';

    protected static $_reg_tpl = '165643';

    protected static $_modify_tpl = '165643';

    protected static $_forget_tpl = '165643';

    //验证支付密码
    public function VerifyPayPass(int $uid, string $pass){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($pass)) throw new ArException(ArException::PAY_PASS_ERROR);

        $member = Members::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::PAY_PASS_ERROR);
        if(!password_verify($pass, $member->PayPassword))
            throw new ArException(ArException::PAY_PASS_ERROR);
    }

    /**
     * @method 获取邀请码
     */
    protected function GetInviteCode(){
        //随机生成再去匹配，不存在就添加
        $code = mt_rand(100000,999999);
        $has = Members::where('InviteCode', $code)->select('Id')->first();
        while(!empty($has)){
            $code = mt_rand(100000,999999);
            $has = Members::where('InviteCode', $code)->select('Id')->first();
        }
        return $code;
    }

    /**
     * @method 验证参数格式
     */
    protected function VerifyParamsData(array $data){
        if(!self::EmailFmt($data['Email']))
            throw new ArException(ArException::SELF_ERROR,'邮箱格式错误');

        if(!self::PassFmt($data['Password'], 8, 20))
            throw new ArException(ArException::SELF_ERROR,'密码长度8-20位,需要包含字母和数字');

        if(!self::PassFmt($data['PayPassword'], 8, 20))
            throw new ArException(ArException::SELF_ERROR,'交易密码长度8-20位,需要包含字母和数字');

        if($data['Password'] != $data['RepeatPassword'])
            throw new ArException(ArException::SELF_ERROR,'两次密码不一致');

        if($data['PayPassword'] != $data['RepeatPayPassword'])
            throw new ArException(ArException::SELF_ERROR,'两次交易密码不一致');
    }

    /**
     * @method 验证注册参数
     */
    protected function VerifyRegParams(array $data){
        //邮箱
        if(empty($data['Email'])) throw new ArException(ArException::SELF_ERROR,'请填写邮箱');
        //密码
        if(empty($data['Password'])) throw new ArException(ArException::SELF_ERROR,'请填写密码');
        //重复密码
        if(empty($data['RepeatPassword'])) throw new ArException(ArException::SELF_ERROR,'请重复密码');
        //交易密码
        if(empty($data['PayPassword'])) throw new ArException(ArException::SELF_ERROR,'请填写交易密码');
        //重复交易密码
        if(empty($data['RepeatPayPassword'])) throw new ArException(ArException::SELF_ERROR,'请重复交易密码');
        //邀请码
        if(empty($data['InviteCode'])) throw new ArException(ArException::SELF_ERROR,'请填写邀请码');
        //验证码
        if(empty($data['AuthCode'])) throw new ArException(ArException::SELF_ERROR,'请填写验证码');
        $this->VerifyParamsData($data);
    }

    //加日志
    protected static function AddLog(int $uid, $money, Coin $coin, string $mold){
        $sort = $uid % 20;
        if($sort < 10) $sort = '0'.$sort;
        $table = 'FinancingList_'.$sort;
        $fina = FinancingMold::where('call_index', $mold)->first();
        if(empty($fina)) return ;
        $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
        if(empty($memberCoin)) throw new ArException(ArException::UNKONW);
        if(bccomp($memberCoin->Money, 0, 10) < 0) throw new ArException(ArException::COIN_NOT_ENOUGH);
        $data = [
            'MemberId' => $uid,
            'Money' => $money,
            'CoinId' => $coin->Id,
            'CoinName' => $coin->EnName,
            'Mold' => $fina->id,
            'MoldTitle' => $fina->title,
            'Remark' => $fina->title,
            'AddTime' => time(),
            'Balance' => $memberCoin->Money
        ];
        DB::table($table)->insert($data);
    }

    /**
     * @method 【原】聚合发送短信
     */
    public function JuHeSmsX($phone, $code, $tpl){
        $url = "http://v.juhe.cn/sms/send";
        $params = array(
            'key'   => self::$juhe_key,
            'mobile'    => $phone,
            'tpl_id'    => $tpl,
            'tpl_value' =>'#code#='.$code
        );

        $paramstring = http_build_query($params);
        $content = SendRequest($url, $paramstring);
        $result = json_decode($content, true);
        if(!is_array($result)) throw new ArException(ArException::SELF_ERROR,'发送失败');
        if(!isset($result['error_code'])) throw new ArException(ArException::SELF_ERROR,'网络错误，请稍后再试');
        if($result['error_code'] != 0) throw new ArException(ArException::SELF_ERROR, $result['reason']);
    }
    
    /**
     * @method 253替代聚合，发送短信
     */
    public function JuHeSms($phone, $code, $tpl){

        require_once('Sms253Api.php');

        $msg = '【EVC】验证码：'.$code.'，请尽快验证，5分钟内有效';
        
        $clapi  = new \Sms253Api();
        //设置您要发送的内容：其中“【】”中括号为运营商签名符号，多签名内容前置添加提交
        $result = $clapi->sendSMS($phone,$msg);

        if(!is_null(json_decode($result))){
            $output=json_decode($result,true);

            if(isset($output['code'])  && $output['code']=='0'){
                return $result;
            }else{
                throw new ArException(ArException::SELF_ERROR, $result['errorMsg']);
            }
            
        }else{
            throw new ArException(ArException::SELF_ERROR,'网络错误，请稍后再试');
        }
    }

    /**
     * @method 七牛上传Token 需要配置 config/filesystem.php
     */
    public function QiniuUpload(){
        $conf = DB::table('QiniuConfig')->first();
        if(empty($conf)) throw new ArException(ArException::SELF_ERROR,'暂时无法上传');
        config([
            'filesystems.disks.qiniu.domains.default' => $conf->Domain,
            'filesystems.disks.qiniu.access_key' => $conf->AccessKey,
            'filesystems.disks.qiniu.secret_key' => $conf->SecretKey,
            'filesystems.disks.qiniu.bucket' => $conf->Bucket
        ]);
        $disk = QiniuStorage::disk('qiniu');
        $token = $disk->uploadToken();
        $data = [
            'token' => $token,
            'domain' => $conf->Domain,
            'region' => $conf->Region
        ];
        return $data;
    }

    /**
     * @method 获取Token
     * @param string $hash hash值
     */
    protected function MakeToken(int $uid, $hash){
        /**
         * iss：发行人
         * exp：到期时间
         * sub：主题
         * aud：用户
         * nbf：在此之前不可用
         * iat：发布时间
        */
        $jwt = JWT::encode(array(
            "iss" => "system",
            "aud" => "user",
            "nbf" => time(),
            "iat" => time(),
            //不设置过期时间，过期时间交给系统控制
            //"exp" => time() + 30,
            "member_id" => $uid,
            "token" => $hash
        ), self::$key);
        $token = base64_encode(openssl_encrypt($jwt, 'DES-EDE3', self::$key,  OPENSSL_RAW_DATA));
        return $token;
    }

}