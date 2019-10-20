<?php

namespace App\Http\Controllers;

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
    public function Question(Request $request, IndexService $service){
        $list = DB::table('CommonQA')->get();
        return self::success($list);
    }

}
