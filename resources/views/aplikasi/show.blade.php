@extends('layouts.app', ['title' => $a->nama])
@section('content')
@php($D = \App\Support\Dates::class)
<a class="back-link" href="{{ route('beranda') }}">‹ Ringkasan</a>
<div class="head"><div><h1>{{ $a->nama }}</h1><small class="mono">{{ $a->kode }}</small></div>
    <div class="row"><a class="btn" href="{{ route('jelajah.aplikasi', $a) }}">🔎 Jelajah data</a>
        <form method="post" action="{{ route('aplikasi.uji', $a) }}">@csrf<button class="btn btn-pri">🧪 Uji pulih sekarang</button></form></div></div>

<nav class="seg" style="margin-bottom:12px">
    <a href="{{ route('aplikasi.show', $a) }}" @class(['on' => ! $jenis])>Semua</a>
    @foreach(\App\Models\Berkas::JENIS as $k => $l)<a href="{{ route('aplikasi.show', [$a, 'jenis' => $k]) }}" @class(['on' => $jenis === $k])>{{ $l }}</a>@endforeach
</nav>
<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Berkas</th><th>Waktu backup</th><th>Ukuran</th><th>Keutuhan</th><th></th></tr></thead>
    <tbody>
    @forelse($berkas as $b)
        <tr><td class="card-title"><a href="{{ route('berkas.show', $b) }}">{{ $b->nama }}</a><br><small class="mut">{{ \App\Models\Berkas::JENIS[$b->jenis] }}</small></td>
            <td data-label="Waktu">{{ $D::waktu($b->waktu) }}</td><td data-label="Ukuran">{{ $D::ukuran($b->ukuran) }}</td>
            <td data-label="Utuh">@if($b->utuh === false)<span class="badge b-bad" title="{{ $b->pesan }}">Rusak</span>@elseif($b->pesan)<span class="badge b-warn" title="{{ $b->pesan }}">Cek</span>@else<span class="badge b-ok">Utuh</span>@endif</td>
            <td class="r"><div class="row">@if($b->jenis !== 'cermin')<a class="btn btn-sm" href="{{ route('berkas.unduh', $b) }}">⬇</a>@endif<a class="btn btn-sm" href="{{ route('berkas.show', $b) }}">Buka</a></div></td></tr>
    @empty
        <tr><td colspan="5"><div class="empty">Belum ada berkas.</div></td></tr>
    @endforelse
    </tbody></table>
    @include('partials.pager', ['p' => $berkas])
</div>

<div class="grid g2">
<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Uji pulih</h2></div>
    @forelse($uji as $u)
        <div class="stu"><span class="nm"><b>{{ $D::waktu($u->waktu) }}</b><small>{{ $u->berkas->nama ?? '(berkas sudah dihapus retensi)' }} · {{ $u->tabel }} tabel · {{ number_format($u->baris, 0, ',', '.') }} baris</small>
            @if($u->pesan)<small @class(['bad' => $u->status === 'gagal', 'warn' => $u->status === 'peringatan'])>{{ $u->pesan }}</small>@endif</span>
            <span class="badge {{ ['ok' => 'b-ok', 'peringatan' => 'b-warn', 'gagal' => 'b-bad'][$u->status] }}">{{ ucfirst($u->status) }}</span></div>
    @empty
        <div class="empty">Belum pernah diuji.</div>
    @endforelse
</div>
<form method="post" action="{{ route('aplikasi.update', $a) }}" class="card">@csrf @method('PUT')
    <h2>Pengaturan aplikasi</h2>
    <div class="field"><label>Nama</label><input type="text" name="nama" value="{{ $a->nama }}" required></div>
    <div class="field"><label>Tabel rahasia (pisahkan koma)</label><input type="text" name="rahasia" value="{{ implode(', ', $a->rahasia ?? []) }}" placeholder="bk_catatan"><small>Dibuka hanya dengan alasan tertulis, dan tercatat di Log.</small></div>
    <div class="grid g2">
        <div class="field"><label>Folder aplikasi di VM</label><input type="text" name="app_dir" value="{{ $a->app_dir }}" placeholder="/var/www/tu-pusat"></div>
        <div class="field"><label>Nama database di VM</label><input type="text" name="db" value="{{ $a->db }}" placeholder="tu_pusat"></div>
    </div>
    <small>Dipakai untuk menyusun langkah pemulihan.</small><br>
    <button class="btn" style="margin-top:8px">Simpan</button>
</form>
</div>
@endsection
