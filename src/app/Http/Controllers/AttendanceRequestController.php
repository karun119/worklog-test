<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\CorrectionRequest;


class AttendanceRequestController extends Controller
{
    public function requestList()
    {
        $userId = Auth::id();
        $requests = CorrectionRequest::with(['user', 'attendance'])
            ->where('user_id', $userId)
            ->where('created_by_admin', false)
            ->orderBy('application_date')
            ->orderBy('id')
            ->get()
            ->map(function ($correctionRequest) {
                $correctionRequest->target_date =
                    $correctionRequest->new_date ?? optional($correctionRequest->attendance)->work_date;

                $correctionRequest->status_label =
                    $correctionRequest->status === 'pending' ? '承認待ち' : '承認済み';

                return $correctionRequest;
            })
            ->groupBy('status');
        $pendingRequests = $requests['pending'] ?? collect();
        $approvedRequests = $requests['approved'] ?? collect();

        return view('request', compact('pendingRequests', 'approvedRequests'));
    }
}
