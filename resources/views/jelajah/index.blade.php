@extends('layouts.app', ['title' => 'Jelajah Data'])
@section('content')
@php($D = \App\Support\Dates::class)
<div class="head"><div><h1>Jelajah Data</h1><small>Isi database semua aplikasi, dari backup terakhir yang lolos uji pulih. Baca saja; kolom password/token selalu disembunyikan.</small></div></div>
<div class="quick">
@forelse($list as $a)
    @php($u = $a->ujiTerakhir)
    <a class="quick-item" href="{{ route('jelajah.aplikasi', $a) }}"><span class="bk-ic" aria-hidden="true">🗄</span>
        <span><b>{{ $a->nama }}</b><small>@if($u && $u->status !== 'gagal'){{ $u->tabel }} tabel · backup {{ $D::waktu($u->berkas?->waktu) }}@elseif($u)Uji pulih gagal @else Belum diuji @endif</small></span></a>
@empty
    <div class="card"><div class="empty">Belum ada aplikasi.</div></div>
@endforelse
</div>
@endsection
