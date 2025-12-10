@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-list">
    <div class="request-list__header">
        <span class="header-bar"></span>
        <h2 class="page__title">申請一覧</h2>
    </div>
    <ul class="status-tabs">
        <li class="tab active" data-target="pending">承認待ち</li>
        <li class="tab" data-target="approved">承認済み</li>
    </ul>
    <div class="request-list__table-wrap">
        <table class="request-list__table table-pending active">
            <thead>
                <tr class="table-row">
                    <th class="table-head table-head--status">状態</th>
                    <th class="table-head table-head--name">名前</th>
                    <th class="table-head table-head--target-date">対象日時</th>
                    <th class="table-head table-head--reason">申請理由</th>
                    <th class="table-head table-head--request-date">申請日時</th>
                    <th class="table-head table-head--detail">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($pendingRequests as $correctionRequest)
                <tr class="table-row">
                    <td class="table-cell table-cell--status">{{ $correctionRequest->status_label }}</td>
                    <td class="table-cell table-cell--name">{{ $correctionRequest->user->name }}</td>
                    <td class="table-cell table-cell--target-date">{{ $correctionRequest->target_date?->format('Y/m/d') ?? '-' }}</td>
                    <td class="table-cell table-cell--reason">{{ $correctionRequest->comment }}</td>
                    <td class="table-cell table-cell--request-date">{{ $correctionRequest->application_date?->format('Y/m/d') }}</td>
                    <td class="table-cell table-cell--detail">
                        @if ($correctionRequest->attendance_id)
                        <a class="detail-link" href="{{ route('attendance.detail', $correctionRequest->attendance_id) }}">詳細</a>
                        @else
                        <a class="detail-link" href="{{ route('attendance.detail', 0) }}?work_date={{ $correctionRequest->target_date->format('Y-m-d') }}">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="no-data-row">
                    <td colspan="6" class="table-cell">承認待ちの申請はありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <table class="request-list__table table-approved" style="display: none;">
            <thead>
                <tr class="table-row">
                    <th class="table-head table-head--status">状態</th>
                    <th class="table-head table-head--name">名前</th>
                    <th class="table-head table-head--target-date">対象日時</th>
                    <th class="table-head table-head--reason">申請理由</th>
                    <th class="table-head table-head--request-date">申請日時</th>
                    <th class="table-head table-head--detail">詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($approvedRequests as $correctionRequest)
                <tr class="table-row">
                    <td class="table-cell table-cell--status">{{ $correctionRequest->status_label }}</td>
                    <td class="table-cell table-cell--name">{{ $correctionRequest->user->name }}</td>
                    <td class="table-cell table-cell--target-date">{{ $correctionRequest->target_date?->format('Y/m/d') ?? '-' }}</td>
                    <td class="table-cell table-cell--reason">{{ $correctionRequest->comment }}</td>
                    <td class="table-cell table-cell--request-date">{{ $correctionRequest->application_date?->format('Y/m/d') }}</td>
                    <td class="table-cell table-cell--detail">
                        @if ($correctionRequest->attendance_id)
                        <a class="detail-link" href="{{ route('attendance.detail', $correctionRequest->attendance_id) }}">詳細</a>
                        @else
                        <a class="detail-link" href="{{ route('attendance.detail', 0) }}?work_date={{ $correctionRequest->target_date->format('Y-m-d') }}">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr class="no-data-row">
                    <td colspan="6" class="table-cell">承認済みの申請はありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/request.js') }}"></script>
@endsection