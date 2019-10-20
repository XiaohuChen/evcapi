<?php
namespace App\Libraries;

use App\Exceptions\ArException;

//密码处理相关 加密 验证 核对等
trait Verify
{

    /**
     * @method 验证密码格式
     * @param string $pass 密码
     * @param int $min 最小位数
     * @param int $max 最大位数
     * @param callback $func 验证函数
     */
    public static function PassFmt(string $pass, int $min, int $max){
        //验证长度
        if(empty($pass)) return false;
        $len = mb_strlen($pass);
        if($len < $min || $len > $max) return false;
        /**
         * ctype_alnum：//检查是否是字母或数字或字母数字的 组合
         * ctype_alpha  //检查字符串是否是纯字母
         * ctype_cntrl  //是否是控制字符如\n,\r,\t
         * ctype_digit  //是否是数字表示的字符
         * ctype_graph  //检查是否有任何可打印字符，除了空格（补）
         * ctype_lower  //检查是否是小写字母
         * ctype_upper  //检查是否是大写字母
         * ctype_space  //是否是空白字符
         * ctype_xdigit //检查是否是十六进制数字
         */
        if(!ctype_alnum($pass)) return false;
        if(ctype_alpha($pass)) return false;
        if(ctype_digit($pass)) return false;

        return true;
    }

    /**
     * @method 验证手机号格式
     * @param mix $phone 手机号
     */
    public function PhoneFmt($phone){
        $patten = '/^1(3|5|6|7|8|9)\d{9}$/';
        return (bool) preg_match($patten, $phone); 
    }

    public function EmailFmt($email){
        $pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/";
        preg_match($pattern, $email, $matches);
        if(empty($matches)) return false;
        return true;
    }

}

