@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_approve.css') }}">
@endsection

@section('content')
<div class="approve-detail">
    <div class="approve-detail__header">
        <span class="header-bar"></span>
        <h2 class="page__title">勤怠詳細</h2>
    </div>
    <form method="POST" action="{{ route('admin.stamp.approve.update', $correction->id) }}">
        @csrf
        <table class="approve-table">
            <tr class="row-divider">
                <th class="label-cell table-th">名前</th>
                <td class="name-field table-td">
                    <div class="field-wrapper">
                        <span class="name-value">
                            {{ optional($correction->user)->name ?? '—' }}
                        </span>
                    </div>
                </td>
            </tr>
            <tr class="row-divider">
                <th class="label-cell table-th">日付</th>
                <td class="date-field table-td">
                    <div class="field-wrapper">
                        <span class="date-year">
                            {{ $correction->new_date->format('Y年') }}
                        </span>
                        <span class="date-monthday">
                            {{ $correction->new_date->format('n月j日') }}
                        </span>
                    </div>
                </td>
            </tr>
            <tr class="row-divider">
                <th class="label-cell table-th">出勤・退勤</th>
                <td colspan="2" class="time-field table-td">
                    <div class="field-wrapper">
                        <div class="field-group clock-in-group">
                            <span class="time-value">
                                {{ $correction->new_clock_in->format('H:i') }}
                            </span>
                        </div>
                        <span class="tilde">〜</span>
                        <div class="field-group clock-out-group">
                            <span class="time-value">
                                {{ $correction->new_clock_out->format('H:i') }}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
            @foreach($correction->correctionBreakTimes ?? collect() as $i => $break)
            <tr class="row-divider">
                <th class="label-cell table-th">休憩{{ $i + 1 }}</th>
                <td colspan="2" class="time-field table-td">
                    <div class="break-row field-wrapper">
                        <div class="field-group break-in-group">
                            <span class="time-value">
                                {{ $break->new_break_in?->format('H:i') ?? '' }}
                            </span>
                        </div>
                        <span class="tilde">〜</span>
                        <div class="field-group break-out-group">
                            <span class="time-value">
                                {{ $break->new_break_out?->format('H:i') ?? '' }}
                            </span>
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
            <tr class="row-divider">
                <th class="label-cell">備考</th>
                <td colspan="2" class="comment-field">
                    <div class="field-wrapper">
                        <div class="field-group">
                            <div class="comment-value">
                                <p>{{ $correction->comment }}</p>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
        <div class="approve-actions">
            @if($isApproved)
            <p class="approve-message">承認済み</p>
            @else
            <button type="submit" class="approve-btn">承認</button>
            @endif
        </div>
    </form>
</div>
@endsection