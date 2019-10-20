<?php

namespace App\Exceptions;

use App\Libraries\Send;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    use Send;
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        //自定义异常直接返回
        if($exception instanceof ArException){
            return self::returnMsg($exception->getMessage(), $exception->getCode());
        }
        if($exception instanceof Exception){
            if(env('APP_DEBUG')){
                //return self::returnMsg($exception->getMessage().'['.$exception->getLine().']', $exception->getCode());
                return parent::render($request, $exception);
            } else {
                return self::returnMsg('网络错误['.$exception->getLine().']', $exception->getCode());
            }
        }

        return parent::render($request, $exception);
    }
}
