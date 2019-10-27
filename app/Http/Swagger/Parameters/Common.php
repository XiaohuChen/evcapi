<?php
// Header 参数
/**
 *  @OA\SecurityScheme(
 *      securityScheme="Authorization",
 *      type="apiKey",
 *      in="header",
 *      name="Authorization",
 *      description="Token检验"
 * )
 */

// 请求参数
/**
 * @OA\Parameter(
 *      parameter="page",
 *      name="page",
 *      description="页数",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="integer",
 *          format="int64",
 *      )
 * )
 * @OA\Parameter(
 *      parameter="count",
 *      name="count",
 *      description="条数",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="integer",
 *          format="int64",
 *      )
 * )
 *
 * @OA\Parameter(
 *      parameter="number",
 *      name="number",
 *      description="数量",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * 
 * @OA\Parameter(
 *      parameter="NoticeId",
 *      name="Id",
 *      description="消息ID",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="NewsId",
 *      name="Id",
 *      description="资讯ID",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * 
 */
