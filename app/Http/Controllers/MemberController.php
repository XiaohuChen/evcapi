<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\MemberService;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{

    /**
     * @OA\Post(
     *     path="/member-forget-paypassword",
     *     operationId="/member-forget-paypassword",
     *     tags={"Member"},
     *     summary="忘记交易密码",
     *     description="忘记交易密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email"),
     *     @OA\Parameter(ref="#/components/parameters/AuthCode"),
     *     @OA\Parameter(ref="#/components/parameters/NewPayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPayPassword")
     * )
     */
    public function ForgetPayPassword(Request $request, MemberService $service){
        $code = intval($request->input('AuthCode'));
        $email = trim($request->input('Email'));
        $pass = trim($request->input('NewPayPassword'));
        $repass = trim($request->input('RepeatPayPassword'));
        $service->ForgetPayPassword($code, $email, $pass, $repass);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/member-forget-password",
     *     operationId="/member-forget-password",
     *     tags={"Member"},
     *     summary="忘记密码",
     *     description="忘记密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email"),
     *     @OA\Parameter(ref="#/components/parameters/AuthCode"),
     *     @OA\Parameter(ref="#/components/parameters/NewPassword"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPassword")
     * )
     */
    public function ForgetPassword(Request $request, MemberService $service){
        $code = intval($request->input('AuthCode'));
        $email = trim($request->input('Email'));
        $pass = trim($request->input('NewPassword'));
        $repass = trim($request->input('RepeatPassword'));
        $service->ForgetPassword($code, $email, $pass, $repass);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/member-modify-avatar",
     *     operationId="/member-modify-avatar",
     *     tags={"Member"},
     *     summary="修改头像",
     *     description="修改头像",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Avatar"),
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
    public function ModifyAvatar(Request $request, MemberService $service){
        $name = trim($request->input('Avatar'));
        $uid = intval($request->get('uid'));
        $service->ModifyAvatar($uid, $name);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/member-modify-nick",
     *     operationId="/member-modify-nick",
     *     tags={"Member"},
     *     summary="修改昵称",
     *     description="修改昵称",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/NickName"),
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
    public function ModifyNickName(Request $request, MemberService $service){
        $name = trim($request->input('NickName'));
        $uid = intval($request->get('uid'));
        $service->ModifyNickName($uid, $name);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/member-modify-paypassword",
     *     operationId="/member-modify-paypassword",
     *     tags={"Member"},
     *     summary="修改交易密码",
     *     description="修改交易密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/OldPayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/PayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPayPassword"),
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
    public function ModifyPayPassword(Request $request, MemberService $service){
        $password = trim($request->input('PayPassword'));
        $repeatPasswrod = trim($request->input('RepeatPayPassword'));
        $oldPassword = trim($request->input('OldPayPassword'));
        $uid = intval($request->get('uid'));
        $service->ModifyPayPassword($uid, $oldPassword, $password, $repeatPasswrod);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/member-modify-password",
     *     operationId="/member-modify-password",
     *     tags={"Member"},
     *     summary="修改登录密码",
     *     description="修改登录密码",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/OldPassword"),
     *     @OA\Parameter(ref="#/components/parameters/Password"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPassword"),
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
    public function ModifyPassword(Request $request, MemberService $service){
        $password = trim($request->input('Password'));
        $repeatPasswrod = trim($request->input('RepeatPassword'));
        $oldPassword = trim($request->input('OldPassword'));
        $uid = intval($request->get('uid'));
        $service->ModifyPassword($uid, $oldPassword, $password, $repeatPasswrod);
        return self::success();
    }

    /**
     * @OA\Get(
     *     path="/member-invite",
     *     operationId="/member-invite",
     *     tags={"Member"},
     *     summary="我的团队上面蓝色框框的数据",
     *     description="我的团队上面蓝色框框的数据",
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
    public function InviteNum(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $in = $service->InviteNum($uid);
        return self::success($in);
    }

    /**
     * @OA\Get(
     *     path="/invite-list",
     *     operationId="/invite-list",
     *     tags={"Member"},
     *     summary="直推列表",
     *     description="直推列表",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/count"),
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
    public function InviteList(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $count = intval($request->input('count'));
        $list = $service->InviteList($uid, $count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/member-info",
     *     operationId="/member-info",
     *     tags={"Member"},
     *     summary="用户资料",
     *     description="用户资料",
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
    public function Info(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $in = $service->Info($uid);
        return self::success($in);
    }

    /**
     * @OA\Post(
     *     path="/member-login",
     *     operationId="/member-login",
     *     tags={"Member"},
     *     summary="账号密码登录",
     *     description="账号密码登录",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email"),
     *     @OA\Parameter(ref="#/components/parameters/Password")
     * )
     */
    public function Login(Request $request, MemberService $service){
        $email = trim($request->input('Email'));
        $password = trim($request->input('Password'));
        //登录
        $token = $service->Login($email, $password);
        return self::success($token);
    }

    /**
     * @OA\Post(
     *     path="/member-register",
     *     operationId="/member-register",
     *     tags={"Member"},
     *     summary="注册",
     *     description="注册",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Email"),
     *     @OA\Parameter(ref="#/components/parameters/Password"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPassword"),
     *     @OA\Parameter(ref="#/components/parameters/PayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/RepeatPayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/InviteCode"),
     *     @OA\Parameter(ref="#/components/parameters/AuthCode"),
     * )
     */
    public function Register(Request $request, MemberService $service){
        $data = [
            'Email' => trim($request->input('Email')),
            'Password' => trim($request->input('Password')),
            'RepeatPassword' => trim($request->input('RepeatPassword')),
            'PayPassword' => trim($request->input('PayPassword')),
            'RepeatPayPassword' => trim($request->input('RepeatPayPassword')),
            'InviteCode' => intval($request->input('InviteCode')),
            'AuthCode' => intval($request->input('AuthCode')),
            'Ip' => $request->getClientIp()
        ];
        //注册
        $service->Register($data);
        return self::success();
    }

    /**
     * @OA\Get(
     *     path="/finace-list",
     *     operationId="/finace-list",
     *     tags={"Stream"},
     *     summary="资金变动列表",
     *     description="资金变动列表",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/TurnoverType"),
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/count"),
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
    public function FinaceList(Request $request, MemberService $service){
        $type = intval($request->input('Type'));
        $uid = intval($request->get('uid'));
        $count = intval($request->input('count'));
        $list = $service->List($uid, $type, $count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/finace-molds",
     *     operationId="/finace-molds",
     *     tags={"Stream"},
     *     summary="资金变动类型",
     *     description="资金变动类型",
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
    public function FinaceMolds(Request $request, MemberService $service){
        $list = $service->Molds();
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/balance-list",
     *     operationId="/balance-list",
     *     tags={"Member"},
     *     summary="收益账户",
     *     description="收益账户",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/count"),
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
    public function Balance(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $count = intval($request->input('count'));
        $list = $service->Balance($uid, $count);
        return self::success($list);
    }

    /**
     * @OA\Post(
     *     path="/auth-member",
     *     operationId="/auth-member",
     *     tags={"Member"},
     *     summary="实名认证",
     *     description="实名认证",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/IdCard"),
     *     @OA\Parameter(ref="#/components/parameters/RealName"),
     *     @OA\Parameter(ref="#/components/parameters/IdCardImg"),
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
    public function Auth(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $idCard = trim($request->input('IdCard'));
        $name = trim($request->input('Name'));
        $imgs = $request->input('IdCardImg');
        $list = $service->Auth($uid, $idCard, $name, $imgs );
        return self::success($list);
    }

    /**
     * @OA\Post(
     *     path="/bind-phone",
     *     operationId="/bind-phone",
     *     tags={"Member"},
     *     summary="绑定手机号",
     *     description="绑定手机号",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Phone"),
     *     @OA\Parameter(ref="#/components/parameters/AuthCode"),
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
    public function BindPhone(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $phone = trim($request->input('Phone'));
        $code = intval($request->input('AuthCode'));
        $list = $service->BindPhone($uid, $phone, $code);
        return self::success($list);
    }

    /**
     * @OA\Post(
     *     path="/unsealing",
     *     operationId="/unsealing",
     *     tags={"Member"},
     *     summary="解封",
     *     description="解封",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
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
    public function Unsealing(Request $request, MemberService $service){
        $uid = intval($request->get('uid'));
        $pass = trim($request->input('PayPassword'));
        $service->VerifyPayPass($uid, $pass);
        $list = $service->Unsealing($uid);
        return self::success($list);
    }

    /**
     * @OA\Post(
     *     path="/balance-withdraw",
     *     operationId="/balance-withdraw",
     *     tags={"Member"},
     *     summary="收益提现",
     *     description="收益提现",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/PayPassword"),
     *     @OA\Parameter(ref="#/components/parameters/WithdrawNumber"),
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
    public function Withdraw(Request $request, MemberService $service){
        $pass = trim($request->input('PayPassword'));
        $uid = intval($request->get('uid'));
        $service->VerifyPayPass($uid, $pass);
        $number = trim($request->input('Number'));
        $service->Withdraw($uid, $number);
        return self::success();
    }

    /**
     * @OA\Get(
     *     path="/withdraw-fee",
     *     operationId="/withdraw-fee",
     *     tags={"Member"},
     *     summary="收益提现手续费比例",
     *     description="收益提现手续费比例",
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
    public function Fee(Request $request, MemberService $service){
        $ratio = 0;
        $setting = DB::table('RatioSetting')->first();
        if(!empty($setting)) $ratio = bcadd($setting->WorldRatio, $setting->BackRatio, 4);
        return self::success($ratio);
    }

}
