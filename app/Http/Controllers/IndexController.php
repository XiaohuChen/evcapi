<?php

namespace App\Http\Controllers;

use App\Exceptions\ArException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\IndexService;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Info(
 *     version="1.0",
 *     title="EVC Api",
 * ),
 * @OA\Server(
 *      description="本地",
 *     url="http://api.evc.com/"
 * ),
 * @OA\Server(
 *      url="http://evc.api.php.8kpay.com:10001/",
 *      description="测试"
 * ),
 * @OA\Server(
 *      url="http://XXX.com",
 *      description="正式"
 * )
 */
class IndexController extends Controller
{

    /**
     * @OA\Get(
     *     path="/notice-list",
     *     operationId="/notice-list",
     *     tags={"Common"},
     *     summary="消息列表",
     *     description="消息列表",
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
    public function NoticeList(Request $request, IndexService $service){
        $count = intval($request->input('count'));
        $list = $service->NoticeList($count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/notice-detail",
     *     operationId="/notice-detail",
     *     tags={"Common"},
     *     summary="消息列表",
     *     description="消息列表",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/NoticeId"),
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
    public function NoticeDetail(Request $request, IndexService $service){
        $id = intval($request->input('Id'));
        $list = $service->NoticeDetail($id);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/banner-list",
     *     operationId="/banner-list",
     *     tags={"Common"},
     *     summary="Banner列表",
     *     description="Banner列表",
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
    public function BannerList(Request $request, IndexService $service){
        $list = $service->BannerList();
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/qiniu-upload",
     *     operationId="/qiniu-upload",
     *     tags={"Common"},
     *     summary="七牛上传Token",
     *     description="七牛上传Token",
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
    public function QiniuUpload(Request $request, IndexService $service){
        $token = $service->QiniuUpload();
        return self::success($token);
    }

    /**
     * @OA\Get(
     *     path="/common-question",
     *     operationId="/common-question",
     *     tags={"Common"},
     *     summary="常见问题",
     *     description="常见问题",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     )
     * )
     */
    public function Question(Request $request, IndexService $service){
        $list = DB::table('CommonQA')->get();
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/member-doc",
     *     operationId="/member-doc",
     *     tags={"Common"},
     *     summary="关于我们&用户协议",
     *     description="关于我们&用户协议",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     )
     * )
     */
    public function Doc(Request $request, IndexService $service){
        $list = DB::table('MemberDoc')->first();
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/etherscan",
     *     operationId="/etherscan",
     *     tags={"Unknow Api"},
     *     summary="Unknow Api - 1",
     *     description="Unknow Api - 1",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/UnkowUrl1")
     * )
     */
    public function etherscan(Request $request, IndexService $service){
        try{
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Content-type:application/x-www-form-urlencoded',
                    'timeout' => 60 * 60 // 超时时间（单位:s）
                ]
            ];
            $url = trim($request->input('UnkowUrl1'));
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            $result = json_decode($result, true);
            return self::success($result);
        } catch(\Exception $e){
            throw new ArException(ArException::SELF_ERROR, $e->getMessage());
        }
        
    }


    /**
     * @OA\Get(
     *     path="/news-list",
     *     operationId="/news-list",
     *     tags={"Index"},
     *     summary="快讯列表",
     *     description="快讯列表",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/page"),
     *     @OA\Parameter(ref="#/components/parameters/count")
     * )
     */
    public function NewList(Request $request, IndexService $service){
        $count = intval($request->input('count'));
        $list = $service->NewsList($count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/news-detail",
     *     operationId="/news-detail",
     *     tags={"Index"},
     *     summary="快讯",
     *     description="快讯",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/NewsId")
     * )
     */
    public function NewsDetail(Request $request, IndexService $service){
        $id = intval($request->input('Id'));
        $list = $service->NewsDetail($id);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/qiniu-domain",
     *     operationId="/qiniu-domain",
     *     tags={"Index"},
     *     summary="获取七牛域名",
     *     description="获取七牛域名",
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
    public function QiniuDomain(Request $request, IndexService $service){
        $res = DB::table('QiniuConfig')->select('Domain')->first();
        return self::success($res);
    }

    /**
     * @OA\Get(
     *     path="/update",
     *     operationId="/update",
     *     tags={"Common"},
     *     summary="更新",
     *     description="更新",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/Version")
     * )
     */
    public function Update(Request $request, IndexService $service){
        $version = trim($request->input('Version'));
        $info = $service->Update($version);
        return self::success($info);
    }

}
