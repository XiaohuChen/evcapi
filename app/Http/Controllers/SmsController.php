<?php

namespace App\Http\Controllers;

use App\Exceptions\ArException;
use App\Http\Controllers\Controller;
use App\Libraries\SendEmail;
use Illuminate\Http\Request;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class SmsController extends Controller
{

    /**
     * @OA\Post(
     *     path="/sms-modify-paypass",
     *     operationId="/sms-modify-paypass",
     *     tags={"SMS"},
     *     summary="忘记交易密码",
     *     description="忘记交易密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Phone")
     * )
     */
    public function ModifyPayPassCode(Request $request, SmsService $service){
        $phone =  trim($request->input('Phone'));

        //注册
        $service->ModifyPayPassCode($phone);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/sms-withdraw",
     *     operationId="/sms-withdraw",
     *     tags={"SMS"},
     *     summary="提现",
     *     description="提现",
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
    public function WithdrawCode(Request $request, SmsService $service){
        $id = intval($request->get('uid'));

        //注册
        $service->WithdrawCode($id);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/sms-modify-pass",
     *     operationId="/sms-modify-pass",
     *     tags={"SMS"},
     *     summary="忘记密码",
     *     description="忘记密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Phone")
     * )
     */
    public function ModifyPassCode(Request $request, SmsService $service){
        $phone =  trim($request->input('Phone'));

        //注册
        $service->ModifyPassCode($phone);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/sms-register-code",
     *     operationId="/sms-register-code",
     *     tags={"SMS"},
     *     summary="发送注册验证码",
     *     description="发送注册验证码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Phone")
     * )
     */
    public function RegisterCode(Request $request, SmsService $service){
        $phone =  trim($request->input('Phone'));

        //注册
        $service->RegisterCode($phone);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/sms-bindphone-code",
     *     operationId="/sms-bindphone-code",
     *     tags={"SMS"},
     *     summary="发送绑定手机验证码",
     *     description="发送绑定手机验证码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Phone")
     * )
     */
    public function BindPhoneCode(Request $request, SmsService $service){
        $phone =  trim($request->input('Phone'));

        //注册
        $service->BindPhoneCode($phone);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/email-register-code",
     *     operationId="/email-register-code",
     *     tags={"SMS"},
     *     summary="邮箱注册验证码",
     *     description="邮箱注册验证码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email")
     * )
     */
    public function EmailRegisterCode(Request $request, SmsService $service){
        $email = trim($request->input('Email'));
        if(!isEmail($email)) throw new ArException(ArException::SELF_ERROR,'邮箱格式错误');
        $has = DB::table('Members')->where('Email', $email)->first();
        if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'该邮箱已注册');

        $service->SendCode($email, 'EmailAuthCode');
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/email-modify-pass",
     *     operationId="/email-modify-pass",
     *     tags={"SMS"},
     *     summary="邮箱忘记密码验证码",
     *     description="邮箱忘记密码验证码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email")
     * )
     */
    public function EmailModifyPassCode(Request $request, SmsService $service){
        $email = trim($request->input('Email'));
        $service->VerifyReg($email);
        $service->SendCode($email, 'ModifyPass');
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/email-modify-pay-pass",
     *     operationId="/email-modify-pay-pass",
     *     tags={"SMS"},
     *     summary="邮箱忘记交易密码验证码",
     *     description="邮箱忘记密码验证码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email")
     * )
     */
    public function EmailModifyPayPassCode(Request $request, SmsService $service){
        $email = trim($request->input('Email'));
        $service->VerifyReg($email);
        $service->SendCode($email, 'ModifyPayPass');
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/email-bind-address",
     *     operationId="/email-bind-address",
     *     tags={"SMS"},
     *     summary="绑定地址验证码",
     *     description="绑定地址验证码",
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
    public function EmailBindAddress(Request $request, SmsService $service){
        $uid = intval($request->get('uid'));
        $member = DB::table('Members')->where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
        $service->SendCode($member->Email, 'BindAddress');
        return self::success();
    }

}
