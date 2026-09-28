<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeSubscriptionPlanRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {
    }

    public function changePlan(ChangeSubscriptionPlanRequest $request,Subscription $subscription): JsonResponse {
        try {
            $change = $this->subscriptionService->changePlan($subscription,Plan::findOrFail($request->new_plan_id),Carbon::parse($request->effective_at));

            return response()->json([
                'message' => 'Subscription plan changed successfully.',
                'data' => $change->load('fromPlan', 'toPlan'),
            ], 200);

        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}