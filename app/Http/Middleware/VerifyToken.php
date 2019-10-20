<?php

namespace App\Http\Middleware;

use App\Exceptions\ArException;
use App\Models\MemberTokenModel as MemberToken;
use App\Models\MembersModel as Member;
use Closure;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerifyToken
{
    public static $key = 'sblw-3hn8-sqoy19sblw-3hn';

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        //token令牌放在Authorization字段中
        $auth = $request->header('Authorization');
        if(empty($auth)) throw new ArException(ArException::NO_LOGIN);

        //对base_64解密，解密后得到jwt密文
        $jwt = openssl_decrypt(base64_decode($auth), 'DES-EDE3', self::$key, OPENSSL_RAW_DATA);
        //对jwt解码 获得用户Id和token
        try{
            $decoded = JWT::decode($jwt, self::$key, ['HS256']);
        } catch(\Exception $e){
            throw new ArException(ArException::ILLEGAL_LOGIN);
        }
        $uid = $decoded->member_id;
        $token = $decoded->token;
        
        //验证token
        $memberToken = MemberToken::where('MemberId', $uid)->first();
        if(empty($memberToken)) throw new ArException(ArException::AUTH_ERROR);
        if($memberToken->Token !== $token) throw new ArException(ArException::TOKEN_ERROR);
        if($memberToken->ExpireTime < time()) throw new ArException(ArException::TOKEN_EXPIRE);
        //验证用户
        $member = Member::where('Id', $uid)->first();
        if(empty($member)) throw new ArException(ArException::USER_NOT_FOUND);
        if($member->IsBan != 0) throw new ArException(ArException::USER_BE_BAN);
        //重设过期时间,过期时间环境变量配置，连续多少天不登录则自动过期
        //$exp = env('LOGIN_EXPIRE_DAY', 7); //默认七天过期
        //DB::table('MemberToken')->where('MemberId', $uid->member_id)->increment('ExpireTime', $exp * 86400);

        $request->attributes->add(['uid' => $uid]);
        return $next($request);
    }
}
