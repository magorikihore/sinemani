<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportManagementController extends Controller
{
    /**
     * List reports.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Report::with(['user:id,name,email', 'reportable']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $reports = $query->orderByDesc('created_at')
            ->paginate($request->input('per_page', 20));

        return $this->paginated($reports);
    }

    /**
     * Review a report.
     */
    public function review(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:reviewed,resolved,dismissed'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'action' => ['nullable', 'string', 'in:delete_content,ban_user,none'],
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        // Take action if specified
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'delete_content':
                    if ($report->reportable instanceof Comment) {
                        $report->reportable->delete();
                    }
                    break;
                case 'ban_user':
                    // Ban the content author, not the reporter
                    if ($report->reportable && method_exists($report->reportable, 'user')) {
                        $report->reportable->user->update(['is_active' => false]);
                    }
                    break;
            }
        }

        return $this->success($report->fresh(), 'Report reviewed');
    }
}
