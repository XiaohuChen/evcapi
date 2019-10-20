<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ProductService;

class ProductController extends Controller
{

    /**
     * @OA\Post(
     *     path="/plan-product",
     *     operationId="/plan-product",
     *     tags={"Product"},
     *     summary="预约",
     *     description="预约",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/ProductId"),
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
    public function Plan(Request $request, ProductService $service){
        $id = intval($request->input('Id'));
        $pass = trim($request->input('PayPassword'));
        $uid = intval($request->get('uid'));
        $service->VerifyPayPass($uid, $pass);
        $service->Plan($uid, $id);
        return self::success();
    }

    /**
     * @OA\Post(
     *     path="/pay-product",
     *     operationId="/pay-product",
     *     tags={"Product"},
     *     summary="报单",
     *     description="报单",
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
    public function Pay(Request $request, ProductService $service){
        $pass = trim($request->input('PayPassword'));
        $uid = intval($request->get('uid'));
        $service->VerifyPayPass($uid, $pass);
        $service->Pay($uid);
        return self::success();
    }

    /**
     * @OA\Get(
     *     path="/product-list",
     *     operationId="/product-list",
     *     tags={"Product"},
     *     summary="产品列表",
     *     description="产品列表",
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
    public function List(Request $request, ProductService $service){
        $count = intval($request->input('count'));
        $list = $service->List($count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/product-detail",
     *     operationId="/product-detail",
     *     tags={"Product"},
     *     summary="产品详情",
     *     description="产品详情",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/ProductId"),
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
    public function Detail(Request $request, ProductService $service){
        $id = intval($request->input('Id'));
        $detail = $service->Detail($id);
        return self::success($detail);
    }

    /**
     * @OA\Get(
     *     path="/my-product",
     *     operationId="/my-product",
     *     tags={"Product"},
     *     summary="我的广告包",
     *     description="我的广告包",
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
    public function MyList(Request $request, ProductService $service){
        $count = intval($request->input('count'));
        $uid = intval($request->get('uid'));
        $list = $service->MyList($uid, $count);
        return self::success($list);
    }

    /**
     * @OA\Get(
     *     path="/my-detail",
     *     operationId="/my-detail",
     *     tags={"Product"},
     *     summary="广告包投资详情",
     *     description="广告包投资详情",
     *     @OA\Response(
     *         response=200,
     *         description="操作成功",
     *         @OA\JsonContent(ref="#/components/schemas/success")
     *     ),
     *     @OA\Parameter(ref="#/components/parameters/MemberProductId"),
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
    public function MyDetail(Request $request, ProductService $service){
        $id = intval($request->input('Id'));
        $uid = intval($request->get('uid'));
        $list = $service->MyDetail($uid, $id);
        return self::success($list);
    }

}
