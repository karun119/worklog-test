@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance-container">
    <div class="status-display">
        <span class="status-text">{{ $status ?? '勤務外' }}</span>
    </div>
    <div class="attendance__datetime">
        @php
        $now = \Carbon\Carbon::now();
        @endphp
        <div class="work-date">
            {{ $now->isoFormat('YYYY年M月D日(ddd)') }}
        </div>
        <div class="work-time" id="work-time">
            {{ $now->format('H:i') }}
        </div>
    </div>
    <form id="attendance-form" action="{{ route('attendance.store') }}" method="POST">
        @csrf
        <input type="hidden" name="action" id="action-input">
        <div class="attendance__buttons">
            @if(!isset($status) || $status === '勤務外')
            <button type="button" class="btn btn-clock-in" onclick="submitAction('clock_in')">
                出勤
            </button>
            @elseif($status === '出勤中')
            <button type="button" class="btn btn-clock-out" onclick="submitAction('clock_out')">
                退勤
            </button>
            <button type="button" class="btn btn-break-in" onclick="submitAction('break_in')">
                休憩入
            </button>
            @elseif($status === '休憩中')
            <button type="button" class="btn btn-break-out" onclick="submitAction('break_out')">
                休憩戻
            </button>
            @elseif($status === '退勤済')
            <div class="finished-message {{ $status === '退勤済' ? 'show' : '' }}">
                お疲れ様でした。
            </div>
            @endif
        </div>
    </form>
</div>
@endsection

@section('js')
<script src="{{ asset('js/attendance.js') }}"></script>
@endsection