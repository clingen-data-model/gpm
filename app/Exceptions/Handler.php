<?php

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

    // app/Exceptions/Handler.php
    public function render($request, Throwable $exception)
{
    if ($exception instanceof ModelNotFoundException) {
        return response()->json([
            'message' => 'Resource not found'
        ], 404);
    }

    if ($exception instanceof AuthenticationException) {
        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }

    if ($exception instanceof \Exception) {
        return response()->json([
            'message' => 'Internal Server Error',
            'error' => $exception->getMessage(), // You can omit this line in production
            'code' => 500
        ], 500);
    }

    return parent::render($request, $exception);
}

}
