<?php


namespace App\Services;

use App\Exceptions\ArException;
use App\Models\CoinModel as Coin;
use App\Models\MemberCoinModel as MemberCoin;
use Illuminate\Support\Facades\DB;
use App\Models\WithdrawModel as Withdraw;
use App\Models\RechargeModel as Recharge;
use App\Libraries\Thrift;
use App\Models\MembersModel as Members;
use Illuminate\Support\Facades\Redis;

class CoinService extends Service
{

    /**
     * 绑定地址
     */
    public function BindAddress(int $uid, $address, int $code){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if(empty($address)) throw new ArException(ArException::SELF_ERROR,'请填写地址');
        DB::beginTransaction();
        try{
            $member = Members::where('Id', $uid)->first();
            if(empty($member))
                throw new ArException(ArException::USER_NOT_FOUND);
            //验证码
            $auth = Redis::hget('BindAddress',$member->Email);
            if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
            $auth = json_decode($auth, true);
            if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
            if($auth['Code'] != $code) throw new ArException(ArException::SELF_ERROR,'验证码错误');
            if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');
            
            if(!empty($member->Address))
                throw new ArException(ArException::SELF_ERROR,'你已绑定地址');
            DB::table('Members')->where('Id', $uid)->update([
                'Address' => $address
            ]);
            DB::commit();
        } catch(ArException  $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR,$e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR,$e->getMessage());
        }
        
    }

    /**
     * @method 钱包总资产(USDT)
     */
    public function TotalBalance(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        $memberCoin = MemberCoin::where('MemberId', $uid)->get();
        $balance = 0;
        foreach($memberCoin as $item){
            $price = bcmul($item->coin->Price, $item->Money, 10);
            $balance = bcadd($balance, $price);
        }
        return $balance;
    }

    //WithdrawResult WithDraw(1:i32 coin,2:i32 member,3:double money,4:string address,5:string memo)
    /**
     * @method 提现
     */
    public function Recharge(int $uid, int $coinId, string $money, string $memo = '', $code = null){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($coinId <= 0) throw new ArException(ArException::PARAM_ERROR);
        if(!is_numeric($money)) throw new ArException(ArException::SELF_ERROR,'金额数量错误');
        $member = Members::where('Id', $uid)->first();
        //验证码
        $auth = Redis::hget('WithdrawCode', $member->Phone);
        if(empty($auth)) throw new ArException(ArException::SELF_ERROR,'请先发送验证码');
        $auth = json_decode($auth, true);
        if(!is_array($auth)) throw new ArException(ArException::SELF_ERROR,'验证码已失效，请重新发送');
        if($auth['Code'] != $code) throw new ArException(ArException::SELF_ERROR,'验证码错误');
        if($auth['ExpireTime'] < time()) throw new ArException(ArException::SELF_ERROR,'验证码已过期');
        DB::beginTransaction();
        try{
            //未实名不可提现
            $member = Members::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
            if($member->AuthState != 2) throw new ArException(ArException::SELF_ERROR,'请先实名认证');
            //未绑定地址不能提现
            if(empty($member->Address)) throw new ArException(ArException::SELF_ERROR,'请先绑定地址');
            $coin = Coin::find($coinId);
            if(empty($coin)) throw new ArException(ArException::COIN_NOT_FOUND);
            //是否可提现
            if (!$coin->IsWithDraw)
                throw new ArException(ArException::SELF_ERROR,'该币种不可提现');
            //最大提现数量 最小提现数量
            if(bccomp($money, $coin->MaxWithDraw, 10) > 0)
                throw new ArException(ArException::SELF_ERROR,'最大提现数量不得大于'.$coin->MaxWithDraw);
            if(bccomp($money, $coin->MinWithDraw, 10) < 0)
                throw new ArException(ArException::SELF_ERROR,'最低提现数量不得低于'.$coin->MinWithDraw);
            //检查余额
            $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
            if(empty($memberCoin)) throw new ArException(ArException::SELF_ERROR,'您未持有'.$coin->EnName);
            if(bccomp($memberCoin->Money, $money, 10) < 0) throw new ArException(ArException::COIN_NOT_ENOUGH);
            //计算手续费
            $fee = bcmul($money, $coin->WithDrawFee, 10);
            if(bccomp($fee, $coin->MinWithDrawFee) < 0)
                $fee = $coin->MinWithDrawFee; //最低手续费
            
            //Recharge Insert
            $recharge = [
                'Address' => $member->Address,
                'Balance' => bcsub($memberCoin->Money, $money, 10),
                'MemberId' => $uid,
                'Mobile' => $memberCoin->member->Phone,
                'CoinId' => $coin->Id,
                'CoinName' => $coin->EnName,
                'Money' => $money,
                'Remark' => $memo,
                'Real' => bcsub($money, $fee, 10),
                'Status' => $coin->IsAutoWithDraw == 1 ? : 0,
                'AddTime' => time(),
                'Fee' => $fee,
                'FeeCoin' => $coin->Id,
                'FeeCoinEname' => $coin->EnName,
                'Hash' => '',
                'ProcessTime' => 0,
                'WithdrawInfo' => '',
                'ProcessMold' => 0
            ];
            $res = DB::table('Withdraw')->insert($recharge);
            if($res !== true) throw new ArException(ArException::SELF_ERROR,'提现失败，请稍后再试');
            //MemberCoin Update
            DB::table('MemberCoin')->where('MemberId', $uid)->where('CoinId', $coin->Id)->update([
                'Money' => DB::raw("Money - {$money}"),
                'Forzen' => DB::raw("Forzen + {$money}")
            ]);
            self::AddLog($uid, $money, $coin, 'withdraw');
            DB::commit();
        } catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        } catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::NET_WORK_ERROR);
        }
    }

    /**
     * @method 获取充值地址
     */
    public function RechargeAddress(int $uid, int $coinId){
        try{
            $thrift = $this->GetThrift();
            $address = $thrift->GetAddress($uid, $coinId);
            if($address->status !== 1) throw new ArException(ArException::SELF_ERROR,'上行获取失败');
            $address = $address->address;
            return $address;
        } catch(ArException $e){
            throw new ArException(ArException::SELF_ERROR,'上行获取失败');
        } catch(\Exception $e){
            throw new ArException(ArException::SELF_ERROR,$e->getMessage());
            throw new ArException(ArException::SELF_ERROR,$e->getMessage());
        }
    }

    /**
     * @method 充值详情
     * @param int $uid 用户Id
     * @param int $id 充值ID
     */
    public function RechargeDetail(int $uid, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);

        $detail = Recharge::where('Id', $id)->where('MemberId', $uid)->first();
        if(empty($detail)) throw new ArException(ArException::PARAM_ERROR);

        return $detail;
    }

    /**
     * @method 提币详情
     * @param int $uid 用户Id
     * @param int $id 提币ID
     */
    public function WithdrawDetail(int $uid, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);

        $detail = Withdraw::where('Id', $id)->where('MemberId', $uid)->first();
        if(empty($detail)) throw new ArException(ArException::PARAM_ERROR);
        // $data = [
        //     'RecvAddress' => $detail->Address,
        //     'Hash' => $detail->Hash,
        //     'Status' => $detail->Status,
        //     'Fee' => $detail->Fee
        // ];
        return $detail;
    }

    /**
     * @method 获取单个币种余额
     * @param int $uid 用户Id
     * @param int $id 币种Id
     */
    public function SingleBalance(int $uid, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);

        $coin = Coin::find($id);
        if(empty($coin)) throw new ArException(ArException::COIN_NOT_FOUND);
        $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $id)->first();
        //没有则添加
        if(empty($memberCoin)){
            $newId = DB::table('MemberCoin')->insertGetId([
                'MemberId' => $uid,
                'CoinId' => $id,
                'CoinName' => $coin->EnName
            ]);
            return [
                'Id' => $newId,
                'CoinId' => $id,
                'MemberId' => $uid,
                'Money' => 0,
                'LockMoney' => 0,
                'Forzen' => 0
            ];
        }
        $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $id)->first();
        $priceCNY = bcmul($memberCoin->tread->PriceCNY, $memberCoin->Money, 10);
        return [
            'Id' => $memberCoin->Id,
            'CoinId' => $memberCoin->CoinId,
            'MemberId' => $memberCoin->MemberId,
            'Money' => number($memberCoin->Money),
            'LockMoney' => number($memberCoin->LockMoney),
            'Forzen' => number($memberCoin->Forzen),
            'PriceCNY' => $priceCNY
        ];
    }

    /**
     * @method 获取币种余额
     * @param int $uid 用户Id
     */
    public function Balance(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        $coins = Coin::where('Status', 1)->get();
        $list = [];
        foreach($coins as $coin){
            $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
            if(empty($memberCoin)){
                $list[] = [
                    'Id' => 0,
                    'CoinId' => $coin->Id,
                    'CoinName' => $coin->EnName,
                    'MemberId' => $uid,
                    'Money' => 0,
                    'LockMoney' => 0,
                    'Forzen' => 0
                ];
            } else {
                $list[] = [
                    'Id' => $memberCoin->Id,
                    'CoinId' => $memberCoin->CoinId,
                    'CoinName' => $memberCoin->CoinName,
                    'MemberId' => $memberCoin->MemberId,
                    'Money' => number($memberCoin->Money),
                    'LockMoney' => number($memberCoin->LockMoney),
                    'Forzen' => number($memberCoin->Forzen)
                ];
            }
        }
        $memberCoin = MemberCoin::where('MemberId', $uid)->get();
        return $list;
    }

    /**
     * @method 根据Id获取币种
     * @param int $id 币种Id
     */
    public function Single(int $id){
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);
        $coin = Coin::find($id);
        if(empty($coin)) throw new ArException(ArException::PARAM_ERROR);
        $data = [
            'Id' => $coin->Id,
            'Name' => $coin->Name,
            'EnName' => $coin->EnName,
            "FullName" => $coin->FullName,
            'Logo' => $coin->Logo,
            'IsWithDraw' => $coin->IsWithDraw,
            'IsRecharge' => $coin->IsRecharge,
            'MinWithDraw' => $coin->MinWithDraw,
            'MaxWithDraw' => $coin->MaxWithDraw,
            'WithDrawFee' => $coin->WithDrawFee,
            'MinWithDrawFee' => $coin->MinWithDrawFee,
            'WithDrawInfo' => $coin->WithDrawInfo,
            'RechargeInfo' => $coin->RechargeInfo,
            'Fixed' => $coin->Fixed,
            'Status' => $coin->Status,
            'Description' => $coin->Description
        ];
        return $data;
    }
    
    /**
     * @method 获取币种列表
     */
    public function List(){
        $list = Coin::get();
        $coins = [];
        foreach($list as $item){
            $coins[] = [
                'Id' => $item->Id,
                'Name' => $item->Name,
                'EnName' => $item->EnName,
                "FullName" => $item->FullName,
                'Price' => $item->Price,
                'Logo' => $item->Logo,
                'IsWithDraw' => $item->IsWithDraw,
                'IsRecharge' => $item->IsRecharge,
                'MinWithDraw' => $item->MinWithDraw,
                'MaxWithDraw' => $item->MaxWithDraw,
                'WithDrawFee' => $item->WithDrawFee,
                'MinWithDrawFee' => $item->MinWithDrawFee,
                'Fixed' => $item->Fixed,
                'Status' => $item->Status,
                'Ext' => $item->Ext,
                'MainAddress' => $item->MainAddress,
                'Protocol' => $item->Protocol,
                'Decimals' => $item->Decimals,
                'WithDrawInfo' => $item->WithDrawInfo,
                'RechargeInfo' => $item->RechargeInfo,
            ];
        }
        return $coins;
    }

    /**
     * @method 获取币种列表
     */
    public function ZList(){
        $list = DB::table('CoinFake')->get();
        $coins = [];
        foreach($list as $item){
            $coins[] = [
                'Id' => $item->Id,
                'Name' => $item->Name,
                'EnName' => $item->EnName,
                "FullName" => $item->FullName,
                'Price' => $item->Price,
                'Logo' => $item->Logo,
                'IsWithDraw' => $item->IsWithDraw,
                'IsRecharge' => $item->IsRecharge,
                'MinWithDraw' => $item->MinWithDraw,
                'MaxWithDraw' => $item->MaxWithDraw,
                'WithDrawFee' => $item->WithDrawFee,
                'MinWithDrawFee' => $item->MinWithDrawFee,
                'Fixed' => $item->Fixed,
                'Status' => $item->Status,
                'Ext' => $item->Ext,
                'MainAddress' => $item->MainAddress,
                'Protocol' => $item->Protocol,
                'Decimals' => $item->Decimals
            ];
        }
        return $coins;
    }

    /**
     * @method 充提记录
     * @param int $uid 用户Id
     * @param int $id 币种Id
     * @param int $count 分页参数
     */
    public function RechargeAndWithdraw(int $uid,  int $count, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($count <= 0) throw new ArException(ArException::PARAM_ERROR);
        if(empty($id)){
            $log = DB::table('RechargeAndWithdraw')->where('MemberId', $uid)->paginate($count);
        } else {
            $log = DB::table('RechargeAndWithdraw')->where('MemberId', $uid)->where('CoinId', $id)->paginate($count);
        }
        $list = [];
        foreach($log as $item){
            $list[] = [
                'Id' => $item->Id,
                'Type' => $item->Type,
                'CoinId' => $item->CoinId,
                'CoinName' => $item->CoinName,
                'Money' => $item->Money,
                'AddTime' => $item->AddTime,
                'Status' => $item->Status
            ];
        }
        return ['list' => $list, 'total' => $log->total()];
    }


    //thrift 客户端
    protected $_thrift = null;

    //获取thrift客户端
    protected function GetThrift(){
        if($this->_thrift !== null) return $this->_thrift;
        $thrift = new Thrift();
        $this->_thrift = $thrift->GetClient();
        return $this->_thrift;
    }

}