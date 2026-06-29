<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function serverError(\Throwable $exception, string $message = 'Unexpected server error', int $status = 500)
    {
        report($exception);

        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $message.': '.$exception->getMessage() : $message,
        ], $status);
    }
}
