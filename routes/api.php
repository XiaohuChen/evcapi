<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/', function () {
    return 'Hallo Word!';
});

//用户
Route::post('member-register', 'MemberController@Register');
Route::post('member-login', 'MemberController@Login');
Route::post('member-forget-password', 'MemberController@ForgetPassword');
Route::post('member-forget-paypassword', 'MemberController@ForgetPayPassword');
//sms
Route::post('sms-register-code', 'SmsController@RegisterCode');
Route::post('sms-modify-pass', 'SmsController@ModifyPassCode');
Route::post('sms-modify-paypass', 'SmsController@ModifyPayPassCode');
Route::post('email-register-code', 'SmsController@EmailRegisterCode');
Route::post('email-modify-pass', 'SmsController@EmailModifyPassCode');
Route::post('email-modify-pay-pass', 'SmsController@EmailModifyPayPassCode');
Route::post('sms-bindphone-code', 'SmsController@BindPhoneCode');
//币种
Route::get('coin-list', 'CoinController@List');

Route::middleware('token')->group(function () {
    Route::get('notice-list', 'IndexController@NoticeList');
    Route::get('notice-detail', 'IndexController@NoticeDetail');
    Route::get('banner-list', 'IndexController@BannerList');
    Route::get('qiniu-upload', 'IndexController@QiniuUpload');
    Route::get('common-question', 'IndexController@Question');
    
    //用户
    Route::post('member-modify-password', 'MemberController@ModifyPassword');
    Route::post('member-modify-paypassword', 'MemberController@ModifyPayPassword');
    Route::post('member-modify-nick', 'MemberController@ModifyNickName');
    Route::post('member-modify-avatar', 'MemberController@ModifyAvatar');
    Route::get('member-invite', 'MemberController@InviteNum');
    Route::get('member-info', 'MemberController@Info');
    Route::get('invite-list', 'MemberController@InviteList');
    Route::get('balance-list', 'MemberController@Balance');
    Route::post('auth-member', 'MemberController@Auth');
    Route::post('bind-phone', 'MemberController@BindPhone');
    Route::post('unsealing ', 'MemberController@Unsealing');
    Route::post('balance-withdraw', 'MemberController@Withdraw');
    Route::get('withdraw-fee', 'MemberController@Fee');
    
    //账户资金
    Route::get('finace-molds', 'MemberController@FinaceMolds');
    Route::get('finace-list', 'MemberController@FinaceList');
    //币种
    Route::get('single-coin', 'CoinController@Single');
    Route::get('coin-balance', 'CoinController@Balance');
    Route::get('coin-single-balance', 'CoinController@SingleBalance');
    Route::get('withdraw-detail', 'CoinController@WithdrawDetail');
    Route::get('recharge-detail', 'CoinController@RechargeDetail');
    Route::get('recharge-address', 'CoinController@RechargeAddress');
    Route::get('recharge-withdraw', 'CoinController@RechargeAndWithdraw');
    Route::post('recharge', 'CoinController@Recharge');
    Route::get('total-balance', 'CoinController@TotalBalance');

    //产品
    Route::post('plan-product', 'ProductController@Plan');
    Route::post('pay-product', 'ProductController@Pay');
    Route::get('product-list', 'ProductController@List');
    Route::get('my-product', 'ProductController@MyList');
    Route::get('my-detail', 'ProductController@MyDetail');
    Route::get('product-detail', 'ProductController@Detail');
    
});



