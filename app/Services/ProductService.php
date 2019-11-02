<?php


namespace App\Services;

use App\Exceptions\ArException;
use App\Models\ProductModel as Product;
use App\Models\MemberProductModel as MemberProduct;
use App\Models\MembersModel as Member;
use App\Models\CoinModel as Coin;
use App\Models\MemberCoinModel as MemberCoin;
use Illuminate\Support\Facades\DB;

class ProductService extends Service
{

    /**
     * @method 产品详情
     */
    public function Detail(int $id){
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);
        
        $product = Product::where('Id', $id)->first();
        if(empty($product)) throw new ArException(ArException::SELF_ERROR,'不存在此产品');

        $detail = [
            'Id' => $product->Id,
            'Name' => $product->Name,
            'NeedLevel' => $product->NeedLevel,
            'Ratio' => $product->Ratio,
            'Doc' => ''
        ];
        return $detail;
    }

    /**
     * @method 投资详情
     */
    public function MyDetail(int $uid, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);
        
        $mpro = MemberProduct::where('MemberId', $uid)->where('Id', $id)->first();
        if(empty($mpro)) throw new ArException(ArException::SELF_ERROR,'你没有此笔投资');

        $coin = Coin::where('EnName','EVC')->first();

        $res = [
            'Id' => $mpro->Id,
            'Name' => $mpro->product->Name,
            'State' => $mpro->State,
            'Ratio' => $mpro->Ratio,
            'AddTime' => $mpro->AddTime,
            'Number' => $mpro->Number,
            'PassTime' => $mpro->PassTime,
            'PayTime' => $mpro->PayTime,
            'NumberEVC' => $mpro->NumberEvc,
            'EvcPrice' => $coin->Price
        ];
        return $res;
    }

    /**
     * @method 我的产品
     */
    public function MyList(int $uid, int $count){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($count <= 0) throw new ArException(ArException::PARAM_ERROR);

        $mpro = MemberProduct::where('MemberId', $uid)->orderBy('Id','desc')->paginate($count);
        $list = [];
        foreach($mpro as $item){
            $list[] = [
                'Id' => $item->Id,
                'Name' => $item->product->Name,
                'State' => $item->State,
                'Ratio' => $item->Ratio,
                'Number' => $item->Number
            ];
        }
        return ['List' => $list, 'Total' => $mpro->total()];
    }

    /**
     * @method 商品列表
     */
    public function List(int $count){
        if($count <= 0) throw new ArException(ArException::PARAM_ERROR);
        $products = Product::where('IsClose', 0)->orderBy('Sort','asc')->paginate($count);
        $list = [];
        foreach($products as $item){
            $list[] = [
                'Id' => $item->Id,
                'Name' => $item->Name,
                'Number' => $item->Number,
                'NeedLevel' => $item->NeedLevel,
                'Ratio' => $item->Ratio
            ];
        }
        return ['List' => $list, 'Total' => $products->total()];
    }

    /**
     * @method 报单
     * @param int $uid
     */
    public function Pay(int $uid){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        DB::beginTransaction();
        try{
            $evc = Coin::where('EnName','EVC')->first();
            if(empty($evc)) throw new ArException(ArException::SELF_ERROR,'不存在币种EVC');
            $memberProduct = MemberProduct::where('MemberId', $uid)->where('State', 3)->first();
            if(empty($memberProduct)) throw new ArException(ArException::SELF_ERROR,'不存在此预约或未放行');

            $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $evc->Id)->first();
            if(empty($memberCoin)) throw new ArException(ArException::SELF_ERROR,'你未持有EVC');
            if(bccomp($memberCoin->Money, $memberProduct->NumberEvc, 10) < 0) throw new ArException(ArException::SELF_ERROR,'EVC余额不足,所需数量'.$memberProduct->NumberEvc);
            DB::table('MemberCoin')->where('MemberId', $uid)->where('CoinId', $evc->Id)->decrement('Money', $memberProduct->NumberEvc);
            self::AddLog($uid, -$memberProduct->NumberEvc, $evc, 'pay_lock');
            DB::table('MemberProducts')->where('Id', $memberProduct->Id)->update(['State' => 1, 'PayTime' => time()]);
            //
            $number = $memberProduct->product->Number;
            DB::table('Members')->where('Id', $uid)->update([
                'HasInv' => 1,
                'Achievement' => DB::raw("Achievement+{$number}")
            ]);
            //增加团推业绩
            $member = Member::where('Id', $uid)->first();
            $root = $this->GetRoot($member->Root);
            DB::table('Members')->whereIn('Id', $root)->increment('TeamAchievement', $number);
            DB::commit();
        } catch(ArException $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }catch(\Exception $e){
            DB::rollBack();
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
    }
    
    public function GetRoot($root){
        $root = explode(',', $root);
        unset($root[count($root)-1]);
        $root = array_values($root);
        unset($root[0]);
        $root = array_values($root);
        return $root;
    }


    /**
     * @method 预约
     * @param int $uid 用户ID
     * @param int $id 产品Id
     */
    public function Plan(int $uid, int $id){
        if($uid <= 0) throw new ArException(ArException::UNKONW);
        if($id <= 0) throw new ArException(ArException::PARAM_ERROR);

        DB::beginTransaction();
        try{
            //封禁的账号不能预约
            $member = Member::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::USER_BE_BAN);
            if($member->IsForbidden != 0)
                throw new ArException(ArException::SELF_ERROR,'此账号已违约封禁，请先解封');
            //可预约时间
            $setting = DB::table('SystemSetting')->first();
            if(empty($setting)) throw new ArException(ArException::SELF_ERROR,'系统配置错误');
            $start = strtotime(date('Y-m-d '.$setting->PlanStart));
            $end = strtotime(date('Y-m-d '.$setting->PlanEnd));
            $time = time();
            if($time < $start || $time > $end)
                throw new ArException(ArException::SELF_ERROR,'未到预约时间');
            //超过每日预约数量不能预约
            $today = intval(date('Ymd'));
            $planNum = MemberProduct::where('PlanDate', $today)->sum('Number');
            if(bccomp($planNum, $setting->PlanNumber, 10) > 0)
                throw new ArException(ArException::SELF_ERROR,'今日预约金额已到上限');
            //未解封的账号不能预约
            $member = Member::where('Id', $uid)->first();
            if(empty($member)) throw new ArException(ArException::SELF_ERROR,'账号异常');
            if($member->IsBan != 0) throw new ArException(ArException::SELF_ERROR,'账号已被封禁');
            //有预约或有当前锁仓，不能预约
            $has = MemberProduct::where('MemberId', $uid)->whereIn('State',[0,1,3])->first();
            if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'此账号当前有存在的投资');

            $product = Product::where('Id', $id)->where('IsClose',0)->first();
            if(empty($product)) throw new ArException(ArException::SELF_ERROR,'不存在此产品或未开启');
            //没达到预约等级不可预约
            if($member->PlanLevel < $product->NeedLevel)
                throw new ArException(ArException::SELF_ERROR,'未达到预约等级');
            //冻结10USDT
            $coin = Coin::where('EnName', 'USDT')->first();
            if(empty($coin)) throw new ArException(ArException::SELF_ERROR,'币种USDT不存在');
            $memberCoin = MemberCoin::where('MemberId', $uid)->where('CoinId', $coin->Id)->first();
            if(empty($memberCoin)) throw new ArException(ArException::SELF_ERROR,'你未持有USDT');
            if(bccomp(10, $memberCoin->Money, 10) > 0) throw new ArException(ArException::SELF_ERROR,'USDT余额不足');
            DB::table('MemberCoin')->where('MemberId', $uid)->where('CoinId', $coin->Id)->update([
                'Money' => DB::raw('Money-10'),
                'Forzen' => DB::raw('Forzen+10')
            ]);
            self::AddLog($uid, 10, $coin, 'lock_store_plan');
            $coinEvc = Coin::where('EnName', 'EVC')->first();
            if(empty($coinEvc)) throw new ArException(ArException::SELF_ERROR,'币种EVC不存在');
            //预约记录
            DB::table('MemberProducts')->insert([
                'MemberId' => $uid,
                'ProductId' => $id,
                'Number' => $product->Number,
                'NumberEvc' => bcdiv($product->Number, $coinEvc->Price), //按evc_usdt计算
                'SurplusDay' => 10,
                'Ratio' => $product->Ratio,
                'AddTime' => time(),
                'PlanDate' => intval(date('Ymd'))
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
    
}
