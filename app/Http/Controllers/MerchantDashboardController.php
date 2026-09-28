<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class MerchantDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    public function show(Merchant $merchant): JsonResponse
    {
        return response()->json(
            $this->dashboardService->getDashboard($merchant)
        );
    }
}
