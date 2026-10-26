@extends('layouts.app', ['title' => 'Sumber'])
@section('content')
@php($D = \App\Support\Dates::class)
@php($tp = request('templat'))
@php($t = $tp === 'master' ? ['jenis' => 'master', 'kode' => 'utama', 'nama' => 'Backup Master Utama', 'folder' => config('bm.data_dir'), 'arsip' => '', 'cermin' => ''] : (isset($templat[$tp]) ? $templat[$tp] + ['kode' => $tp] : []))
<div class="head"><div><h1>Sumber</h1><small>Tempat backup ditarik. Backup Master yang menghubungi sumber (bukan sebaliknya), dengan akses baca-saja.</small></div></div>

@unless($kunci)<div class="alert alert-warn"><span class="alert-txt">Kunci SSH belum ada di <code>{{ config('bm.ssh_key') }}</code>. Jalankan ulang <code>deploy/install.sh</code>.</span></div>@endunless

<div class="card card-tight">
@forelse($list as $s)
    <a class="stu" style="color:inherit" href="{{ route('sumber.show', $s) }}">
        <span class="nm"><b>{{ $s->nama }}</b> <small class="mono mut">{{ $s->kode }}</small>
            <small>{{ \App\Models\Sumber::JENIS[$s->jenis] }} · {{ $s->alamat() }} · tiap hari ≥ {{ $s->jam }} · berhasil {{ $D::lalu($s->terakhir_berhasil) }}</small>
            @if($s->terakhir_status === 'gagal')<small class="bad">{{ \Illuminate\Support\Str::limit($s->terakhir_pesan, 200) }}</small>@endif</span>
        @unless($s->aktif)<span class="badge">Nonaktif</span>@endunless
        <span class="badge {{ ['ok' => 'b-ok', 'gagal' => 'b-bad'][$s->terakhir_status] ?? '' }}">{{ $s->terakhir_status ? ucfirst($s->terakhir_status) : 'Belum' }}</span></a>
@empty
    <div class="empty">Belum ada sumber.</div>
@endforelse
</div>

<div class="card">
    <h2>Tambah sumber</h2>
    <p>Isi otomatis dari templat:
        @foreach($templat as $k => $x)<a class="btn btn-sm" href="{{ route('sumber.index', ['templat' => $k]) }}#tambah">{{ $x['nama'] }}</a> @endforeach
        <a class="btn btn-sm" href="{{ route('sumber.index', ['templat' => 'master']) }}#tambah">Backup Master utama (untuk salinan)</a></p>
    <form id="tambah" method="post" action="{{ route('sumber.store') }}">@csrf @include('sumber._form', ['s' => null, 't' => $t])
        <button class="btn btn-pri">Tambah</button></form>
</div>
@endsection
