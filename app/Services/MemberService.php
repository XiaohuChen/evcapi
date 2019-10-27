<?php


namespace App\Services;

use App\Exceptions\ArException;
use App\Models\MembersModel as Members;
use Ofcold\IdentityCard\IdentityCard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\FinaceMoldModel as FinaceMold;
use App\Models\CoinModel as Coin;
use App\Models\MemberCoinModel as MemberCoin;

class MemberService extends Service
{

    /**
     * @method 收益余额提现
     * @param int $uid 用户Id
     * @param float $number 提现数量
     */
    public function Withdraw(int $uid, $number){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(!is_numeric($number)) throw new ArException(ArException::SELF_ERROR,'提现数量错误');
        DB::beginTransaction();
        try{
            $member = Members::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
            if(bccomp($member->Balance, $number, 10) < 0)
                throw new ArException(ArException::SELF_ERROR, '收益余额不足');
            $coin = Coin::where('EnName','USDT')->first();
            if(empty($coin)) throw new ArException(ArException::SELF_ERROR,'币种USDT不存在');
            //提现时收取一定比例的手续费（回购比例+全球分红）
            $setting = DB::table('RatioSetting')->first();
            if(empty($setting)) throw new ArException(ArException::SELF_ERROR,'奖励比例未设置');
            $dec = $number;
            $feeRatio = bcadd($setting->WorldRatio, $setting->BackRatio, 4);
            $fee = bcmul($number, $feeRatio, 10);
            //扣掉手续费
            $number = bcsub($number, $fee, 10);
            $mCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
            if(empty($mCoin)){
                DB::table('MemberCoin')->insert([
                    'MemberId' => $uid,
                    'CoinId' => $coin->Id,
                    'Money' => $number
                ]);
            } else {
                DB::table('MemberCoin')->where('MemberId', $uid)->where('CoinId', $coin->Id)->increment('Money', $number);
            }
            self::AddLog($uid, $number, $coin, 'balance_withdraw');
            //扣掉余额
            DB::table('Members')->where('Id', $uid)->decrement('Balance', $dec);
            //记录收益余额明细
            DB::table('RewardRecord')->insert([
                'MemberId' => $uid,
                'Number' => -$dec,
                'Type' => 7,
                'AddTime' => time()
            ]);
            DB::commit();
        } catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
    }

    /**
     * @method 解封
     * @param int $uid 
     */
    public function Unsealing(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        DB::beginTransaction();
        try{
            $coin = Coin::where('EnName','USDT')->first();
            if(empty($coin)) throw new ArException(ArException::SELF_ERROR,'币种USDT不存在');
            $member = Members::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
            if($member->IsForbidden != 1) throw new ArException(ArException::SELF_ERROR,'此账号没有封禁');
            $memberCoin = MemberCoin::where('CoinId', $coin->Id)->where('MemberId', $uid)->first();
            if(empty($memberCoin)) throw new ArException(ArException::SELF_ERROR,'你未持有USDT');
            if(bccomp($memberCoin->Money, 50, 10) < 0)
                throw new ArException(ArException::SELF_ERROR,'USDT余额不足');
            DB::table('MemberCoin')->where('CoinId', $coin->Id)->where('MemberId', $uid)->decrement('Money', 50);
            DB::table('Members')->where('Id', $uid)->update(['IsForbidden' => 0]);
            self::AddLog($uid, -50, $coin, 'unsealing');
            DB::commit();
        } catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR,$e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
    }

    /**
     * @method 绑定手机
     */
    public function BindPhone(int $uid, $phone, int $code){
        $auth = Redis::hget('BindPhoneCode', $phone);
        if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
        $auth = json_decode($auth, true);
        if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
        if($auth['Code'] != $code) throw new ArException(ArException::SELF_ERROR,'验证码错误');
        if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');

        DB::beginTransaction();
        try{
            $member = Members::where('Id', $uid)->first();
            if(!empty($member->Phone)) throw new ArException(ArException::SELF_ERROR,'此账号已绑定手机号');
            $has = Members::where('Phone', $phone)->first();
            if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'该手机号已绑定其他账号');
            DB::table('Members')->where('Id', $uid)->update([
                'Phone' => $phone
            ]);
            Redis::hdel('BindPhoneCode', $phone);
            DB::commit();
        } catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
    }

    /**
     * @method 实名认证
     * @param int $uid 用户Id
     * @param string $idCard 身份证号
     * @param string $name 真实姓名
     */
    public function Auth(int $uid, $idCard, $name, $imgs){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        
        if(!IdentityCard::make($idCard))
            throw new ArException(ArException::SELF_ERROR,'身份证号码错误');

        $imgs = json_decode($imgs, true);
        if(!is_array($imgs)) throw new ArException(ArException::SELF_ERROR,'图片格式错误');
        if(count($imgs) != 2) throw new ArException(ArException::SELF_ERROR,'图片数量错误');
        $imgs = json_encode($imgs);

        DB::beginTransaction();
        try{
            $member = Members::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
            if($member->AuthState == 1) throw new ArException(ArException::SELF_ERROR,'等待审核通过');
            if($member->AuthState == 2) throw new ArException(ArException::SELF_ERROR,'此账号已实名认证');
            $has = Members::where('IdCard', $idCard)->where('Id', '<>', $uid)->first();
            if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'此身份证号已被实名认证');
            DB::table('Members')->where('Id', $uid)->update([
                'AuthState' => 1,
                'IdCard' => $idCard,
                'AuthName' => $name,
                'IdCardImg' => $imgs
            ]);
            DB::commit();
        }catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());            
        }
        
        
    }

    /**
     * @method 收益账户
     */
    public function Balance(int $uid, int $count){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($count <= 0) throw new ArException(ArException::PARAM_ERROR);
        
        $balance = 0;
        $member = Members::where('Id', $uid)->first();
        if(!empty($member)) $balance = $member->Balance;

        $log = DB::table('RewardRecord')->where('MemberId', $uid)->paginate($count);
        $list = [];
        foreach($log as $item){
            $list[] = [
                'Id' => $item->Id,
                'Number' => $item->Number,
                'Type' => $item->Type,
                'AddTime' => $item->AddTime
            ];
        }
        return ['List' => $list, 'Total' => $log->total(), 'Balance' => $balance];
    }

    /**
     * @method 用户信息
     * @param int $uid
     */
    public function Info(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        $member = Members::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
        $info = [
            'Id' => $member->Id,
            'NickName' => $member->NickName,
            'Phone' => $member->Phone,
            'IsBindPhone' => empty($member->Phone) ? 0 : 1,
            'Email' => $member->Email,
            'Avatar' => $member->Avatar,
            'InviteCode' => $member->InviteCode,
            'RegTime' => $member->RegTime,
            'IdCard' => $member->IdCard,
            'AuthName' => $member->AuthName,
            'AuthState' => $member->AuthState,
            'Achievement' => $member->Achievement,
            'IsForbidden' => $member->IsForbidden,
            'PlanLevel' => $member->PlanLevel
        ];
        return $info;
    }

    /**
     * @method 忘记交易密码
     * @param $code 验证码
     * @param $phone 手机号
     * @param $pass 新交易密码
     * @param $repass 重复交易密码
     */
    public function ForgetPayPassword($code, $email, $pass, $repass){
        $auth = Redis::hget('ModifyPayPass',$email);
        if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
        $auth = json_decode($auth, true);
        if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
        if($auth['Code'] != $code) throw new ArException(ArException::SELF_ERROR,'验证码错误');
        if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');

        if(strlen($pass) < 8 || strlen($pass) > 20) throw new ArException(ArException::SELF_ERROR,'交易密码长度8-20位');
        if(!ctype_alnum($pass)) throw new ArException(ArException::SELF_ERROR,'交易密码只能包含数字和字母');
        if($pass != $repass) throw new ArException(ArException::SELF_ERROR,'两次交易密码不一致');
        
        $member = Members::where('Email', $email)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);

        $password = password_hash($pass, PASSWORD_DEFAULT);
        DB::table('Members')->where('Email', $email)->update(['PayPassword' => $password]);
        Redis::hdel('ModifyPayPass', $email);
    }

    /**
     * @method 忘记密码
     * @param $code 验证码
     * @param $phone 手机号
     * @param $pass 新密码
     * @param $repass 重复密码
     */
    public function ForgetPassword($code, $email, $pass, $repass){
        $auth = Redis::hget('ModifyPass',$email);
        if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
        $auth = json_decode($auth, true);
        if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
        if($auth['Code'] != $code) throw new ArException(ArException::SELF_ERROR,'验证码错误');
        if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');

        if(strlen($pass) < 8 || strlen($pass) > 20) throw new ArException(ArException::SELF_ERROR,'密码长度8-20位');
        if(!ctype_alnum($pass)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if(ctype_digit($pass)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if(ctype_alpha($pass)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if($pass != $repass) throw new ArException(ArException::SELF_ERROR,'两次密码不一致');
        
        $member = Members::where('Email', $email)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);

        $password = password_hash($pass, PASSWORD_DEFAULT);
        DB::table('Members')->where('Email', $email)->update(['Password' => $password]);
        Redis::hdel('ModifyPass', $email);
    }

    /**
     * @method 修改头像
     * @param int $uid 用户Id
     * @param $avatar 头像
     */
    public function ModifyAvatar(int $uid, $avatar){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($avatar)) throw new ArException(ArException::SELF_ERROR,'请上传头像');
        
        DB::table('Members')->where('Id', $uid)->update(['Avatar' => $avatar]);
    }

    /**
     * @method 修改昵称
     * @param int $uid 用户Id
     * @param $name 昵称
     */
    public function ModifyNickName(int $uid, string $name){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($name)) throw new ArException(ArException::SELF_ERROR,'请填写昵称');
        
        DB::table('Members')->where('Id', $uid)->update(['NickName' => $name]);
    }

    /**
     * @method 修改交易密码
     * @param int $uid 用户ID
     * @param $password 密码
     * @param $repeatPassword 重复密码
     */
    public function ModifyPayPassword(int $uid, $oldPassword, $password, $repeatPassword){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($password)) throw new ArException(ArException::SELF_ERROR,'请输入交易密码');
        if(strlen($password) < 8 || strlen($password) > 20) throw new ArException(ArException::SELF_ERROR,'交易密码长度8-20位');
        if(!ctype_alnum($password)) throw new ArException(ArException::SELF_ERROR,'交易密码只能包含数字和字母');
        if($password !== $repeatPassword) throw new ArException(ArException::SELF_ERROR,'两次交易密码不一致');

        //验证
        $member = Members::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::UNKONW);
        if(!password_verify($oldPassword, $member->PayPassword))
            throw new ArException(ArException::SELF_ERROR,'原交易密码错误');
        if(password_verify($password, $member->PayPassword))
            throw new ArException(ArException::SELF_ERROR,'新交易密码不得与原交易密码相同');

        $payPassword = password_hash($password, PASSWORD_DEFAULT);
        $res = DB::table('Members')->where('Id', $uid)->update(['PayPassword' => $payPassword]);
        if(empty($res)) throw new ArException(ArException::SELF_ERROR,'修改失败,请稍后再试');
    }

    /**
     * @method 修改密码
     * @param int $uid 用户ID
     * @param $password 密码
     * @param $repeatPassword 重复密码
     */
    public function ModifyPassword(int $uid, $oldPassword, $password, $repeatPassword){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($password)) throw new ArException(ArException::SELF_ERROR,'请输入密码');
        if(strlen($password) < 8 || strlen($password) > 20) throw new ArException(ArException::SELF_ERROR,'密码长度8-20位');
        if(!ctype_alnum($password)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if(ctype_digit($password)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if(ctype_alpha($password)) throw new ArException(ArException::SELF_ERROR,'密码只能包含数字和字母');
        if($password !== $repeatPassword) throw new ArException(ArException::SELF_ERROR,'两次密码不一致');

        //验证
        $member = Members::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::UNKONW);
        if(!password_verify($oldPassword, $member->Password)) throw new ArException(ArException::SELF_ERROR,'原密码错误');
        if(password_verify($password, $member->Password)) throw new ArException(ArException::SELF_ERROR,'新密码不得与原密码相同');

        $password = password_hash($password, PASSWORD_DEFAULT);
        $res = DB::table('Members')->where('Id', $uid)->update(['Password' => $password]);
        if(empty($res)) throw new ArException(ArException::SELF_ERROR,'修改失败,请稍后再试');
    }

    /**
     * @method 登录
     * @param $phone 电话
     * @param $pass 密码
     */
    public function Login($email, $pass){
        //验证用户、密码
        $member = Members::where('Email', $email)->first();
        if(empty($member)) throw new ArException(ArException::ACCOUNT_NOT_FOUND);
        if(!password_verify($pass, $member->Password)) throw new ArException(ArException::ACCOUNT_NOT_FOUND);
        if($member->IsBan != 0) throw new ArException(ArException::USER_BE_BAN);
        //token
        $hash = md5(microtime(true));
        $token = $this->MakeToken($member->Id,$hash);
        $res = DB::table('MemberToken')->updateOrInsert([
            'MemberId' => $member->Id
        ],[
            'Token' => $hash,
            'ExpireTime' => time() + 7 * 86400,  //过期时间七天
            'Mold' => 1
        ]);
        if(empty($res)) throw new ArException(ArException::NET_WORK_ERROR);
        return $token;
    }

    /**
     * @method 注册
     */
    public function Register(array $data){
        //验证参数
        $this->VerifyRegParams($data);

        $parentId = 0;
        $root = [];
        //邀请码
        $pMember = Members::where('InviteCode', $data['InviteCode'])->first();
        if(empty($pMember)) throw new ArException(ArException::SELF_ERROR,'邀请码错误');
        $parentId = $pMember->Id;
        $root = trim($pMember->Root);
        if(empty($root))
            $root = ",{$pMember->Id},";
        else
            $root .= "{$pMember->Id},";
        //邮箱
        $has = Members::where('Email', $data['Email'])->first();
        if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'账号已存在');
        //验证码
        $auth = Redis::hget('EmailAuthCode',$data['Email']);
        if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
        $auth = json_decode($auth, true);
        if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
        if($auth['Code'] != $data['AuthCode']) throw new ArException(ArException::SELF_ERROR,'验证码错误');
        if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');
        DB::beginTransaction();
        try{
            //生成邀请码
            $inviteCode = $this->GetInviteCode();
            $data = [
                'Email' => $data['Email'],
                'NickName' => $data['Email'],
                'Password' => password_hash($data['Password'], PASSWORD_DEFAULT),
                'PayPassword' => password_hash($data['PayPassword'], PASSWORD_DEFAULT),
                'ParentId' => $parentId,
                'Root' => $root,
                'RegTime' => time(),
                'RegIp' => ip2long($data['Ip']),
                'InviteCode' => $inviteCode
            ];
            $newId = DB::table('Members')->insertGetId($data);
            //给上级用户加上四代内的用户
            $FourthRoot = $pMember->FourthRoot;
            if(empty($pMember->FourthRoot)){
                $FourthRoot .= $newId;
            } else {
                $FourthRoot .= '|'.$newId;
            }
            DB::table('Members')->where('Id', $pMember->Id)->update([
                'FourthRoot' => $FourthRoot
            ]);
            //从邀请人中取出三级代理保存未4级内代理的用户Id
            $thirt = $this->GetRoot($pMember->Root);
            foreach($thirt as $proxyId){
                $member = Members::where('Id', $proxyId)->first();
                $FourthRoot = $member->FourthRoot;
                if(empty($member->FourthRoot)){
                    $FourthRoot .= $newId;
                } else {
                    $FourthRoot .= '|'.$newId;
                }
                DB::table('Members')->where('Id', $member->Id)->update([
                    'FourthRoot' => $FourthRoot
                ]);
            }
            //删除Redis保存的数据
            Redis::hdel('AuthCode', $data['Email']);
            DB::commit();
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
        
    }

    //从邀请人中取出三级代理保存4级内代理的用户Id
    private function GetRoot($root){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        $root = array_slice($root, -3);
        return array_reverse($root);
    }

    /**
     * @method 获得直推人数、团队人数
     * @param int $uid 用户Id
     */
    public function InviteNum(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        //
        $member = Members::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
        //社区收益
        $ratio = 0;
        $lv = '普通会员';
        $level = DB::table('CommunityLevelSetting')->where('Level', $member->CommunityLevel)->first();
        if(!empty($level)){
            $ratio = $level->Ratio;
            $lv = $level->Name;
        }
        //直推人数
        $zt = Members::where('ParentId', $uid)->count('Id');
        $tNum = Members::where('Root','like',"%,{$uid},%")->count('Id');
        return ['Invite' => $zt, 'Team' => $tNum, 'TeamAchievement' => $member->TeamAchievement, 'Ratio' => $ratio, 'Level' => $lv];
    }

    /**
     * @method 获得直推列表
     * @param int $uid 用户Id
     * @param int $count 分页参数
     */
    public function InviteList(int $uid, int $count){
        //直推人数
        $zt = Members::where('ParentId',$uid)->paginate($count);
        $list = [];
        foreach($zt as $item){
            //等级
            $lv = '普通会员';
            $level = DB::table('CommunityLevelSetting')
                ->where('Level', $item->CommunityLevel)->first();
            if(!empty($level)) $lv = $level->Name;
            //团队人数
            $tNum = Members::where('Root','like',"%,{$item->Id},%")->count();
            $list[] = [
                'Id' => $item->Id,
                'Avatar' => $item->Avatar,
                'Name' => $item->NickName,
                'Level' => $lv,
                'TeamNumber' => $tNum,
                'Phone' => empty($item->Phone) ? '' : replaceMobile($item->Phone),
                'Achievement' => $item->Achievement,
                'TeamAchievement' => $item->TeamAchievement
            ];
        }
        return ['list' => $list, 'total' => $zt->total()];
    }

    /**
     * @method 资金变动列表
     * @param int $tpye 变动类型
     */
    public function List(int $uid, int $type, int $count){
        $sort = $uid % 20;
        if($sort < 10) $sort = '0'.$sort;
        $table = 'FinancingList_'.$sort;
        $list = DB::table($table);
        if($type > 0) $list = $list->where('Mold', $type);
        $list = $list->paginate($count);
        $data = [];
        foreach($list as $item){
            $data[] = [
                'Id' => $item->Id,
                'CoinName' => $item->CoinName,
                'AddTime' => $item->AddTime,
                'MoldTitle' => $item->MoldTitle,
                'Money' => $item->Money,
                'Balance' => $item->Balance,
                'Remark' => $item->Remark
            ];
        }
        return ['list' => $data, 'total' => $list->total()];
    }

    /**
     * @method 资金变动类型
     */
    public function Molds(){
        $list = FinaceMold::get();
        return $list;
    }
}