<?php
/**
 * @OA\Schema(
 *      schema="MachineDetail",
 *      type="object",
 *      allOf={
 *          @OA\Schema(
 *              @OA\Property(property="status", description="状态", type="integer", default="1"),
 *              @OA\Property(property="msg", description="信息", type="string", default="操作成功"),
 *              @OA\Property(
 *                  property="data",
 *                  type="object",
 *                  allOf={
 *                      @OA\Schema(
 *                          @OA\Property(property="Period", description="期次", type="integer"),
 *                          @OA\Property(property="LuckJackpotListId", description="关联奖池ID", type="integer"),
 *                          @OA\Property(property="MemberId", description="用户ID", type="integer"),
 *                          @OA\Property(property="AddTime", description="添加时间", type="integer"),
 *                          @OA\Property(property="Money", description="变动金额（<0未中奖  >0中奖）", type="decimal"),
 *                          @OA\Property(property="mobile", description="关联奖池ID", type="integer"),
 *                      )
 *                  }
 *              )
 *          )
 *      }
 *  )
 */