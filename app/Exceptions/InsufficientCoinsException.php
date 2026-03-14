<?php

namespace App\Exceptions;

use Exception;

class InsufficientCoinsException extends Exception
{
    protected int $required;
    protected int $available;

    public function __construct(int $required, int $available)
    {
        $this->required = $required;
        $this->available = $available;
        parent::__construct("Insufficient coin balance. Required: {$required}, Available: {$available}");
    }

    public function render($request)
    {
        return response()->json([
            'success' => false,
            'message' => 'You don\'t have enough coins to complete this action.',
            'error_code' => 'INSUFFICIENT_COINS',
            'coins_required' => $this->required,
            'coins_available' => $this->available,
        ], 422);
    }
}
