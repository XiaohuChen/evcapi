<?php

/**
 * 替换手机号
 * @param $mobile 手机号
 * @param string $data 替换字符串
 * @return string|string[]|null
 */
function replaceMobile($mobile, $data = '****')
{
    $pattern = '/(\d{3})(\d{4})(\d{4})/i';
    $replacement = "$1$data$3";
    return preg_replace($pattern, $replacement, $mobile);
}

/**
 * 数字转型
 * @param int $digital 数字
 * @param int $number 转型保留个数
 * @return string
 */
function number($digital = 0, $number = 8)
{
    return sprintf('%.' . $number . 'F', $digital);
}

/**
 * 时区
 * @param $time
 * @param float|int $jetLag
 * @return false|float|int|string
 */
function timeZone($time, $jetLag = 60 * 60 * 8)
{
    if (is_numeric($time))
        return $time - $jetLag;
    else
        return date('Y-m-d H:i:s', strtotime($time) - $jetLag);
}

/**
 * 是否是手机号码
 * @param $mobile 手机号码
 * @return bool
 */
function isMobile($mobile)
{
    if (preg_match("/^1[34578]\d{9}$/", $mobile))
        return true;
    else
        return false;
}


/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function SendRequest($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}