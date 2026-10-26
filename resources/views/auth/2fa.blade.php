@extends('layouts.app', ['title' => 'Kode verifikasi', 'bare' => true])
@section('content')
<div class="card login">
    <h1>Kode verifikasi</h1>
    <p class="mut">Buka aplikasi authenticator di HP Anda, lalu ketik 6 angka untuk Backup Master.</p>
    <form method="post" action="{{ route('login.2fa') }}" autocomplete="off">
        @csrf
        <div class="field"><label for="k">Kode 6 angka</label><input id="k" type="text" name="kode" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" required autofocus class="mono" style="font-size:22px;letter-spacing:.2em;text-align:center"></div>
        <button class="btn btn-pri" style="width:100%">Lanjut</button>
    </form>
    <p class="mut" style="margin-top:12px"><small>HP hilang? Admin lain di server: <code>php artisan bm:buat-admin &lt;username&gt; --reset-2fa</code></small></p>
</div>
@endsection
