@extends('layouts.app', ['title' => 'Data rahasia'])
@section('content')
<a class="back-link" href="{{ route('jelajah.aplikasi', $a) }}">‹ {{ $a->nama }}</a>
<form method="post" action="{{ route('jelajah.alasan', [$a, $tabel]) }}" class="card" style="max-width:640px">@csrf
    <h1>🔒 {{ $tabel }}</h1>
    <p>Tabel ini berisi data rahasia (mis. catatan konseling). Tulis alasan membukanya; alasan, nama Anda, dan waktunya tercatat di Log. Izin berlaku 30 menit.</p>
    <div class="field"><label for="al">Alasan</label><textarea id="al" name="alasan" rows="3" required minlength="10" maxlength="500" placeholder="mis. memeriksa hasil pemulihan setelah server BK rusak"></textarea></div>
    <button class="btn btn-pri">Buka</button>
</form>
@endsection
