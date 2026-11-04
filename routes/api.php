<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ApiController;

Route::prefix('v1')->group(function () {
    Route::get('/status', [ApiController::class, 'status']);
    Route::get('/learners', [ApiController::class, 'learners']);
    Route::get('/fee-estimate', [ApiController::class, 'feeEstimate']);
    Route::post('/mpesa/callback', [ApiController::class, 'mpesaCallback']);
});

