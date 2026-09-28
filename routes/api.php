<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UsageController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\MerchantDashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('throttle:usage')->post('/usage',[UsageController::class, 'store']);
Route::post('/subscriptions/{subscription}/change-plan',[SubscriptionController::class, 'changePlan']);
Route::get('/merchants/{merchant}/dashboard',[MerchantDashboardController::class, 'show']);