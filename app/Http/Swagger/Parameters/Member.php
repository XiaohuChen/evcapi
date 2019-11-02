<?php
/**
 * @OA\Parameter(
 *      parameter="Phone",
 *      name="Phone",
 *      description="手机号",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Password",
 *      name="Password",
 *      description="密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="RepeatPassword",
 *      name="RepeatPassword",
 *      description="重复密码",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="PayPassword",
 *      name="PayPassword",
 *      description="支付密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="RepeatPayPassword",
 *      name="RepeatPayPassword",
 *      description="重复支付密码",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="InviteCode",
 *      name="InviteCode",
 *      description="邀请码",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="AuthCode",
 *      name="AuthCode",
 *      description="验证码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="OldPassword",
 *      name="OldPassword",
 *      description="原密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="OldPayPassword",
 *      name="OldPayPassword",
 *      description="原交易密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="NickName",
 *      name="NickName",
 *      description="昵称",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Avatar",
 *      name="Avatar",
 *      description="头像",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="NewPassword",
 *      name="NewPassword",
 *      description="新密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="NewPayPassword",
 *      name="NewPayPassword",
 *      description="新交易密码",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="TurnoverType",
 *      name="Type",
 *      description="类型Id 0为全部",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Email",
 *      name="Email",
 *      description="邮箱 ",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="IdCard",
 *      name="IdCard",
 *      description="身份证号",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="RealName",
 *      name="Name",
 *      description="真实姓名",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="IdCardImg",
 *      name="IdCardImg",
 *      description="身份证照片,数组字符串",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="WithdrawNumber",
 *      name="Number",
 *      description="提现数量",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Version",
 *      name="Version",
 *      description="版本",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * 
 */