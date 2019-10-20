<?php
namespace App\Libraries;


trait Send
{
	/**
	 * 返回成功
	 */
	public static function returnMsg($message = '',$status = 1,$data = [],$header = [])
	{
//		http_response_code(200);    //设置返回头部
        $return['status'] = (int)$status;
        $return['message'] = $message;
        $return['data'] = $data;
        // 发送头部信息
        foreach ($header as $name => $val) {
            if (is_null($val)) {
                header($name);
            } else {
                header($name . ':' . $val);
            }
        }
      exit(json_encode($return,JSON_UNESCAPED_UNICODE));
	}

    /**
     * 返回成功
     */
    public static function success($data = []){
        self::returnMsg('操作成功', 1, $data);
    }
}

