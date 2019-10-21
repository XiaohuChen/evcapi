<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\CoinService;
use Illuminate\Http\Request;

class CoinController extends Controller
{

    /**
     * @OA\Get(
     *     path="/coin-list",
     *     operationId="/coin-list",
     *     tags={"Coin"},
     *     summary="币种列表",
     *     description="币种列表",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     )
     * )
     */
    public function List(Request $request, CoinService $service){
        $list = $service->List();
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/single-coin",
     *     operationId="/single-coin",
     *     tags={"Coin"},
     *     summary="根据币种Id获取币种",
     *     description="根据币种Id获取币种",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/CoinId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function Single(Request $request, CoinService $service){
        $id = intval($request->input('Id'));
        $list = $service->Single($id);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/coin-balance",
     *     operationId="/coin-balance",
     *     tags={"Coin"},
     *     summary="获取币种余额",
     *     description="获取币种余额",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function Balance(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $list = $service->Balance($uid);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/coin-single-balance",
     *     operationId="/coin-single-balance",
     *     tags={"Coin"},
     *     summary="获取单个币种余额",
     *     description="获取单个币种余额",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/CoinId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function SingleBalance(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $id = intval($request->input('Id')); //币种Id
        $list = $service->SingleBalance($uid, $id);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/withdraw-detail",
     *     operationId="/withdraw-detail",
     *     tags={"Coin"},
     *     summary="提币详情",
     *     description="提币详情",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/WithdrawId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function WithdrawDetail(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $id = intval($request->input('Id')); //币种Id
        $list = $service->WithdrawDetail($uid, $id);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/recharge-detail",
     *     operationId="/recharge-detail",
     *     tags={"Coin"},
     *     summary="充值详情",
     *     description="充值详情",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/RechargeId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function RechargeDetail(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $id = intval($request->input('Id')); //币种Id
        $detail = $service->RechargeDetail($uid, $id);
        return self::success($detail);
    }

    /**
     * @OA\Get(
     *     path="/recharge-address",
     *     operationId="/recharge-address",
     *     tags={"Coin"},
     *     summary="获取充值地址",
     *     description="获取充值地址",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/CoinId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function RechargeAddress(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $id = intval($request->input('Id'));
        $address = $service->RechargeAddress($uid, $id);
        return self::success($address);
    }

    /**
     * @OA\Get(
     *     path="/recharge-withdraw",
     *     operationId="/recharge-withdraw",
     *     tags={"Coin"},
     *     summary="充提记录",
     *     description="充提记录",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/count"),
     *     @OA\Parameter(ref="#/components/parameters/CoinId"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function RechargeAndWithdraw(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $count = intval($request->input('count'));
        $list = $service->RechargeAndWithdraw($uid, $count);
        return self::success($list);
    }

    /**
     * @OA\Post(
     *     path="/recharge",
     *     operationId="/recharge",
     *     tags={"Coin"},
     *     summary="提现",
     *     description="提现",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/CoinId"),
     *     @OA\Parameter(ref="#/components/parameters/Money"),
     *     @OA\Parameter(ref="#/components/parameters/Address"),
     *     @OA\Parameter(ref="#/components/parameters/Memo"),
     *     @OA\Parameter(ref="#/components/parameters/AuthCode"),
     *     @OA\Parameter(ref="#/components/parameters/PayPassword"),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function Recharge(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $pass = trim($request->input('PayPassword'));
        $service->VerifyPayPass($uid, $pass);
        $coinId = intval($request->input('Id'));
        $money = trim($request->input('Money'));
        $address = trim($request->input('Address'));
        $memo = trim($request->input('Id'));
        $code = $request->input('AuthCode');
        $list = $service->Recharge($uid, $coinId, $money, $address, $memo, $code);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/total-balance",
     *     operationId="/total-balance",
     *     tags={"Coin"},
     *     summary="钱包总资产(USDT)",
     *     description="钱包总资产(USDT)",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Header(
     *         header="api_key",
     *         description="Api key header",
     *         required=true,
     *         @OA\Schema(
     *             type="string"
     *         )
     *     ),
     *     security={
     *          {"Authorization":{}}
     *     }
     * )
     */
    public function TotalBalance(Request $request, CoinService $service){
        $uid = intval($request->get('uid'));
        $balance = $service->TotalBalance($uid);
        return self::success($balance);
    }
}
