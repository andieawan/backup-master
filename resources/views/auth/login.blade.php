@extends('layouts.app', ['title' => 'Masuk', 'bare' => true])
@section('content')
<div class="card login">
    <img class="logo-login" src="{{ asset('icons/icon-192.png') }}" alt="" width="56" height="56">
    <h1>Backup Master</h1>
    <p class="mut">{{ config('bm.lokasi') }} · {{ config('bm.school_name') }}<br>Khusus administrator.</p>
    <form method="post" action="{{ route('login') }}" autocomplete="off">
        @csrf
        <div class="field"><label for="u">Username</label><input id="u" type="text" name="username" value="{{ old('username') }}" autocapitalize="none" autocomplete="username" required autofocus></div>
        <div class="field"><label for="pw">Password</label><input id="pw" type="password" name="password" autocomplete="current-password" required></div>
        <button class="btn btn-pri" style="width:100%" data-busy="Memeriksa…">Masuk</button>
    </form>
</div>
@endsection
