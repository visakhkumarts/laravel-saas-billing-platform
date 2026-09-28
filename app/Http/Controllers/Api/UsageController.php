<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecordUsageRequest;
use App\Services\UsageService;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

class UsageController extends Controller
{
    public function __construct(
        private readonly UsageService $usageService
    ) {}

    public function store(RecordUsageRequest $request): JsonResponse
    {
        try {
            $result = $this->usageService->record(
                $request->validated()
            );

            return response()->json([
                'message' => $result['duplicate'] ? 'Usage event already recorded.': 'Usage event recorded successfully.',
                'data' => $result['event'],
                'duplicate' => $result['duplicate'],
            ], $result['duplicate'] ? 200 : 201);

        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
