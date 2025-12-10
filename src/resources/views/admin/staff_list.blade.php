@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_list.css') }}">
@endsection

@section('content')
<div class="staff-list">
    <div class="staff-list__header">
        <span class="header-bar"></span>
        <h2 class="page__title">スタッフ一覧</h2>
    </div>
    <div class="table-wrap">
        <table class="staff-list__table">
            <thead>
                <tr class="table-row">
                    <th class="table-head">名前</th>
                    <th class="table-head">メールアドレス</th>
                    <th class="table-head">月次勤怠</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($staffs as $staff)
                <tr class="table-row">
                    <td class="table-cell">{{ $staff->name }}</td>
                    <td class="table-cell">{{ $staff->email }}</td>
                    <td class="table-cell">
                        <a class="detail-link" href="{{ route('admin.attendance.staff', ['id' => $staff->id]) }}">
                            詳細
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection