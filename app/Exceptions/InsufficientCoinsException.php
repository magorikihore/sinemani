<?php

namespace App\Exceptions;

use Exception;

class InsufficientCoinsException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => 'INSUFFICIENT_COINS',
        ], 422);
    }
}
