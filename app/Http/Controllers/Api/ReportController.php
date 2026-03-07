<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Models\Comment;
use App\Models\Drama;
use App\Models\Episode;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * Submit a report.
     */
    public function store(StoreReportRequest $request): JsonResponse
    {
        $typeMap = [
            'comment' => Comment::class,
            'drama' => Drama::class,
            'episode' => Episode::class,
        ];

        $reportableType = $typeMap[$request->reportable_type] ?? null;

        if (!$reportableType) {
            return $this->error('Invalid reportable type.', 422);
        }

        // Check if the reportable entity exists
        $reportable = $reportableType::find($request->reportable_id);
        if (!$reportable) {
            return $this->notFound('The reported content was not found.');
        }

        // Prevent duplicate reports
        $existingReport = Report::where('user_id', $request->user()->id)
            ->where('reportable_type', $reportableType)
            ->where('reportable_id', $request->reportable_id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return $this->error('You have already reported this content.', 422);
        }

        $report = Report::create([
            'user_id' => $request->user()->id,
            'reportable_type' => $reportableType,
            'reportable_id' => $request->reportable_id,
            'reason' => $request->reason,
            'description' => $request->description,
        ]);

        return $this->created($report, 'Report submitted. We will review it shortly.');
    }
}
