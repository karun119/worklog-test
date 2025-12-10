@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/verify.css') }}">
@endsection

@section('content')
<div class="verify-status-wrapper">
    @if (session('message'))
    <div class="verify-status-message">
        {{ session('message') }}
    </div>
    @endif
</div>
<div class="verify-container">
    <div class="verify-box">
        <p class="verify-message">
            登録していただいたメールアドレスに認証メールを送付しました。<br>
            メール認証を完了してください。
        </p>
        <a href="http://localhost:8025" target="_blank" rel="noopener noreferrer" class="verify-btn">
            認証はこちらから
        </a>
        <form method="POST" action="{{ route('verification.send') }}" class="verify-form">
            @csrf
            <button type="submit" class="verify-resend-btn">
                認証メールを再送する
            </button>
        </form>
    </div>
</div>
@endsection