@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_list.css') }}">
@endsection

@section('content')
@if(session('success'))
<div class="flash-message">
    {{ session('success') }}
</div>
@endif
<div class="attendance-list">
    <div class="attendance-list__header">
        <span class="header-bar"></span>
        <h2 class="page__title">{{ $currentDate->format('Y年n月j日') }}の勤怠</h2>
    </div>
    <div class="date-switch">
        <a href="{{ route('admin.attendance.list', ['date' => $prevDate->format('Y-m-d')]) }}" class="date-prev">
            <img src="{{ asset('images/arrow.png') }}" alt="前日" class="prev-img">
            <span class="label-prev">前日</span>
        </a>
        <div class="date-current">
            @if(!$isToday)
            <a href="{{ route('admin.attendance.list') }}" class="current-date-link date-decoration">
                <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
                <span class="current-date">{{ $currentDate->format('Y/m/d') }}</span>
            </a>
            @else
            <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
            <span class="current-date">
                {{ $currentDate->format('Y/m/d') }}</span>
            @endif
        </div>
        <a href="{{ route('admin.attendance.list', ['date' => $nextDate->format('Y-m-d')]) }}" class="date-next">
            <span class="label-next">翌日</span>
            <img src="{{ asset('images/arrow.png') }}" alt="翌日" class="next-img">
        </a>
    </div>
    <div class="table-wrap">
        <table class="attendance-list__table">
            <thead>
                <tr class="table-row">
                    <th class="table-head">名前</th>
                    <th class="table-head">出勤</th>
                    <th class="table-head">退勤</th>
                    <th class="table-head">休憩</th>
                    <th class="table-head">合計</th>
                    <th class="table-head">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                <tr class="table-row">
                    <td class="table-cell">
                        {{ $attendance->user->name }}
                    </td>
                    <td class="table-cell">
                        {{ $attendance->clock_in?->format('H:i') }}
                    </td>
                    <td class="table-cell">
                        @if ($attendance->clock_out && $attendance->clock_out->format('H:i') !== '23:59')
                        {{ $attendance->clock_out->format('H:i') }}
                        @else
                        {{-- 退勤忘れは非表示 --}}
                        @endif
                    </td>
                    <td class="table-cell">
                        {{ $attendance->total_break_time?->format('G:i') }}
                    </td>
                    <td class="table-cell">
                        @if ($attendance->total_work_time && $attendance->clock_out?->format('H:i') !== '23:59')
                        {{ $attendance->total_work_time->format('G:i') }}
                        @else
                        {{-- 合計も非表示 --}}
                        @endif
                    </td>
                    <td class="table-cell">
                        @if ($attendance->work_date->isFuture() || ($attendance->work_date->isToday() && !$attendance->clock_out))
                        -
                        @else
                        <a class="detail-link"
                            href="{{ $attendance->id
                                ? route('admin.attendance.detail', ['id' => $attendance->id])
                                : route('admin.attendance.detail', ['id' => 0, 'work_date' => $attendance->work_date->format('Y-m-d'),
                                'user_id' => $attendance->user->id])
                            }}">
                            詳細
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection