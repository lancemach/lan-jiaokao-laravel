<?php
/*
 * @Author: your name
 * @Date: 2021-01-30 09:21:17
 * @LastEditTime: 2021-03-30 12:13:21
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \laravel-lancema\app\Exceptions\Handler.php
 */

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{


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
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    // 重写异常响应
    public function render($request, Throwable $e)
    {
        // 😍😍😍 拥有这行，等于拥有了整个世界 😍😍😍
        $response = parent::render($request, $e);
        return response()->json(export_data(
            $response->status(),
            null,
            // stringDefinedLength($e->getMessage(), 180, 180)
            $e->getMessage()
        ));
    }


}
