<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        return response()->json($this->reportService->userPortfolio($request->user()));
    }
}
