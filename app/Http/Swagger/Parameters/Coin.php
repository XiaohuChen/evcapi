<?php
/**
 * @OA\Parameter(
 *      parameter="CoinId",
 *      name="Id",
 *      description="币种Id",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="WithdrawId",
 *      name="Id",
 *      description="提现ID",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="RechargeId",
 *      name="Id",
 *      description="充值ID",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="integer",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Money",
 *      name="Money",
 *      description="金额",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Address",
 *      name="Address",
 *      description="地址",
 *      in="query",
 *      required=true,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * @OA\Parameter(
 *      parameter="Memo",
 *      name="Memo",
 *      description="提现备注",
 *      in="query",
 *      required=false,
 *      @OA\Schema(
 *          type="string",
 *      )
 * )
 * 
 * 
 */