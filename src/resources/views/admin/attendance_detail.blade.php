@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <div class="attendance-detail__header">
        <span class="header-bar"></span>
        <h2 class="page__title">勤怠詳細</h2>
    </div>
    <form method="POST"
        action="{{ route('admin.attendance.update', [
            'id' => $attendance->id ?? 0,
            'work_date' => $attendance->work_date?->format('Y-m-d')
        ]) }}">
        @csrf
        <input type="hidden" name="user_id" value="{{ $attendance->user->id }}">
        <table class="attendance-table">
            <tr class="row-divider">
                <th class="label-cell table-th">名前</th>
                <td colspan="2" class="name-field table-td">
                    <div class="content-wrapper">
                        <span class="name-value">{{ $attendance->user->name }}</span>
                    </div>
                </td>
            </tr>
            <tr class="row-divider">
                <th class="label-cell table-th">日付</th>
                <td colspan="2" class="date-field table-td">
                    <div class="content-wrapper">
                        <span class="date-year">{{ $attendance->work_date->format('Y年') }}</span>
                        <span class="date-monthday">{{ $attendance->work_date->format('n月j日') }}</span>
                    </div>
                </td>
            </tr>
            <tr class="row-divider">
                <th class="label-cell table-th">出勤・退勤</th>
                <td colspan="2" class="time-field table-td">
                    <div class="content-wrapper">
                        <div class="input-group">
                            <input type="text" name="clock_in"
                                value="{{ old('clock_in', optional($attendance->clock_in)->format('H:i')) }}"
                                class="input-time" {{ $isEditable ? '' : 'readonly' }}>
                        </div>
                        <span class="tilde">〜</span>
                        <div class="input-group">
                            @php
                            $clockOut = ($attendance->clock_out && $attendance->clock_out->format('H:i') !== '23:59')
                            ? $attendance->clock_out->format('H:i')
                            : '';
                            @endphp
                            <input type="text"
                                name="clock_out"
                                value="{{ old('clock_out', $clockOut) }}"
                                class="input-time"
                                {{ $isEditable ? '' : 'readonly' }}>
                        </div>
                    </div>
                    <div class="error-wrapper">
                        @error('clock_in')
                        <div class="error-message">{{ $message }}</div>
                        @enderror
                        @error('clock_out')
                        <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>
                </td>
            </tr>
            @php
            // 休憩行数を動的に制御するためのロジック
            $existingBreaks = $attendance->breakTimes ?? collect();
            $oldBreakIns = old('break_in', []);
            $oldBreakOuts = old('break_out', []);

            if ($isEditable) {
            $rowCount = max($existingBreaks->count() + 1, count($oldBreakIns));
            } else {

            $rowCount = max($existingBreaks->count(), count($oldBreakIns));
            }

            if ($rowCount === 0 && $initialLoad && $isEditable) {
            $rowCount = 1;
            }
            @endphp

            @for ($i = 0; $i < $rowCount; $i++)
                @php
                $breakIn=$oldBreakIns[$i]
                ?? optional($existingBreaks[$i] ?? null)->break_in?->format('H:i')
                ?? '';

                $breakOut = $oldBreakOuts[$i]
                ?? optional($existingBreaks[$i] ?? null)->break_out?->format('H:i')
                ?? '';

                $shouldDisplay = $isEditable || $breakIn || $breakOut;
                @endphp

                @if ($shouldDisplay)
                <tr class="row-divider">
                    <th class="label-cell table-th">
                        {{ $i === 0 ? '休憩' : '休憩' . ($i + 1) }}
                    </th>
                    <td colspan="2" class="time-field table-td">
                        <div class="break-container">
                            <div class="break-row content-wrapper">
                                <div class="input-group">
                                    <input type="text"
                                        name="break_in[{{ $i }}]"
                                        value="{{ $breakIn }}"
                                        class="input-time"
                                        {{ $isEditable ? '' : 'readonly' }}>
                                </div>
                                <span class="tilde">〜</span>
                                <div class="input-group">
                                    <input type="text"
                                        name="break_out[{{ $i }}]"
                                        value="{{ $breakOut }}"
                                        class="input-time"
                                        {{ $isEditable ? '' : 'readonly' }}>
                                </div>
                            </div>
                            <div class="error-wrapper">
                                @error("break_in.$i")
                                <div class="error-message">{{ $message }}</div>
                                @enderror

                                @error("break_out.$i")
                                <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </td>
                </tr>
                @endif
                @endfor
                <tr class="row-divider">
                    <th class="label-cell">備考</th>
                    <td colspan="2" class="comment-field">
                        <div class="content-wrapper">
                            <div class="input-group">
                                <textarea name="comment" class="textarea-comment" {{ $isEditable ? '' : 'readonly' }}>{{ old('comment', $attendance->comment) }}</textarea>
                            </div>
                        </div>
                        <div class="error-wrapper">
                            @error('comment')
                            <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                    </td>
                </tr>
        </table>
        <div class="detail-actions">
            @if ($isEditable)
            <button type="submit" class="request-btn">修正</button>
            @else
            <p class="actions-message">※承認待ちのため修正はできません。</p>
            @endif
        </div>
    </form>
</div>
@endsection
@section('js')
<script src="{{ asset('js/attendance_detail.js') }}"></script>
@endsection