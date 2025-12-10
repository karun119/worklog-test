<?php

namespace App\Http\Controllers;

use App\Models\CorrectionRequest;


class AdminRequestController extends Controller
{

    public function requestIndex()
    {
        $requests = CorrectionRequest::with(['user', 'attendance'])
            ->whereHas('user', fn($q) => $q->where('admin_status', 'general'))
            ->where('created_by_admin', false)
            ->orderBy('application_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($correctionRequest) {
                $correctionRequest->target_date = $correctionRequest->new_date
                    ?? optional($correctionRequest->attendance)->work_date;

                $correctionRequest->status_label =
                    $correctionRequest->status === 'pending' ? '承認待ち' : '承認済み';

                return $correctionRequest;
            })
            ->groupBy('status');
        $pendingRequests = $requests['pending'] ?? collect();
        $approvedRequests = $requests['approved'] ?? collect();

        return view('admin.request', compact('pendingRequests', 'approvedRequests'));
    }

    public function approveShow($attendance_correct_request_id)
    {
        $correction = CorrectionRequest::with(['user', 'correctionBreakTimes'])
            ->findOrFail($attendance_correct_request_id);

        $correction->setRelation(
            'correctionBreakTimes',
            $correction->correctionBreakTimes->filter(
                fn($bt) => $bt->new_break_in || $bt->new_break_out
            )
        );

        return view('admin.request_approve', [
            'correction'     => $correction,
            'isApproved'     => $correction->status === 'approved',
        ]);
    }

    public function approveUpdate($requestId)
    {
        $correction = CorrectionRequest::findOrFail($requestId);
        $correction->status = 'approved';
        $correction->save();

        return redirect()
            ->route('admin.request.list')
            ->with('success', '申請を承認しました');
    }
}