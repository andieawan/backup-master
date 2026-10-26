@extends('layouts.app', ['title' => 'Akun'])
@section('content')
<div class="head"><div><h1>Akun saya</h1><small>{{ '@'.$u->username }}</small></div></div>
<div class="grid g2">
<div class="card">
    <h2>Verifikasi dua langkah</h2>
    @if($u->punya2fa())
        <p><span class="badge b-ok">Aktif</span> sejak {{ \App\Support\Dates::waktu($u->totp_aktif_pada) }}.</p>
        <p class="mut"><small>Ganti HP: minta admin lain menjalankan <code>php artisan bm:buat-admin {{ $u->username }} --reset-2fa</code> di server, lalu atur ulang.</small></p>
    @else
        <ol style="padding-left:18px;line-height:1.7">
            <li>Pasang aplikasi authenticator di HP (Google Authenticator, Microsoft Authenticator, atau Aegis).</li>
            <li>Pilih <b>Tambah akun → Masukkan kunci penyiapan</b>. Nama: <b>Backup Master</b>, kunci:<br>
                <code class="mono" style="font-size:16px;word-break:break-all;user-select:all">{{ trim(chunk_split($rahasia, 4, ' ')) }}</code><br><small>Jenis: berbasis waktu.</small></li>
            <li>Ketik 6 angka yang muncul:</li>
        </ol>
        <form method="post" action="{{ route('akun.2fa') }}" class="row" autocomplete="off">@csrf
            <input type="text" name="kode" inputmode="numeric" maxlength="7" required class="mono" style="max-width:160px;font-size:18px" placeholder="123456">
            <button class="btn btn-pri">Aktifkan</button>
        </form>
        <details style="margin-top:10px"><summary><small>Tautan otpauth (untuk pembuat QR sendiri)</small></summary><code style="word-break:break-all;font-size:12px">{{ $uri }}</code></details>
    @endif
</div>
<form method="post" action="{{ route('akun.password') }}" class="card">@csrf @method('PUT')
    <h2>Ganti password</h2>
    <div class="field"><label>Password lama</label><input type="password" name="lama" required autocomplete="current-password"></div>
    <div class="field"><label>Password baru (min. 12 karakter)</label><input type="password" name="baru" required minlength="12" autocomplete="new-password"></div>
    <div class="field"><label>Ulangi password baru</label><input type="password" name="ulang" required minlength="12" autocomplete="new-password"></div>
    <button class="btn">Simpan</button>
</form>
</div>
@endsection
