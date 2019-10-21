<?php

namespace App\Http\Controllers;

use App\Exceptions\ArException;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Mail;

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
        $subject = '注册验证码';
        $to = trim($request->input('Email'));
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        preg_match($pattern, $to, $matches);
        if(empty($matches)) throw new ArException(ArException::SELF_ERROR,'邮箱格式错误');
        $has = DB::table('Members')->where('Email', $to)->first();
        if(!empty($has)) throw new ArException(ArException::SELF_ERROR,'该邮箱已注册');
        $code = mt_rand(100000, 999999);
$code = 101101;
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('EmailAuthCode', $to, json_encode($auth));
return self::success();

        Mail::send(
            'emails.code',
            [
                'title' => '欢迎注册',
                'code' => $code
            ],
            function ($message) use($to, $subject) { 
                $message->to($to)->subject($subject); 
            }
        );
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
        $subject = '修改密码';
        $to = trim($request->input('Email'));
        $has = DB::table('Members')->where('Email', $to)->first();
        if(empty($has)) throw new ArException(ArException::SELF_ERROR,'该邮箱未注册');
        $code = mt_rand(100000, 999999);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('ModifyPass', $to, json_encode($auth));
        Mail::send(
            'emails.code',
            [
                'title' => '修改密码',
                'code' => $code
            ],
            function ($message) use($to, $subject) { 
                $message->to($to)->subject($subject);
            }
        );
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
        $subject = '修改交易密码';
        $to = trim($request->input('Email'));
        $has = DB::table('Members')->where('Email', $to)->first();
        if(empty($has)) throw new ArException(ArException::SELF_ERROR,'该邮箱未注册');
        $code = mt_rand(100000, 999999);
        $auth = [
            'Code' => $code,
            'ExpireTime' => time() + 600,
            'SendTime' => time()
        ];
        Redis::hset('ModifyPayPass', $to, json_encode($auth));
        Mail::send(
            'emails.code',
            [
                'title' => '修改交易密码',
                'code' => $code
            ],
            function ($message) use($to, $subject) { 
                $message->to($to)->subject($subject);
            }
        );
        return self::success();
    }

}
