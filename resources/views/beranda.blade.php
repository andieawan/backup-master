@extends('layouts.app', ['title' => 'Ringkasan'])
@section('content')
@php($D = \App\Support\Dates::class)
@php($bermasalah = collect($status)->filter(fn ($r) => $r['masalah'])->count())
<div class="head"><div><h1>Ringkasan Backup</h1><small>{{ config('bm.lokasi') }} · data di <code>{{ config('bm.data_dir') }}</code></small></div></div>

<div class="kpis kpis-4">
    <div class="kpi"><small>Aplikasi</small><div class="n">{{ count($status) }}</div><small>{{ $bermasalah ? $bermasalah.' perlu perhatian' : 'semua sehat' }}</small></div>
    <div class="kpi"><small>Sumber gagal</small><div class="n {{ $sumber->where('terakhir_status', 'gagal')->count() ? 'bad' : 'ok' }}">{{ $sumber->where('terakhir_status', 'gagal')->count() }}</div><small>dari {{ $sumber->where('aktif', true)->count() }} sumber aktif</small></div>
    <div class="kpi"><small>Total tersimpan</small><div class="n">{{ $D::ukuran(collect($status)->sum('ukuran')) }}</div><small>{{ collect($status)->sum('jumlah') }} berkas &amp; potret</small></div>
    <div class="kpi"><small>Disk</small><div class="n {{ ($disk['persen'] ?? 0) >= 90 ? 'bad' : (($disk['persen'] ?? 0) >= 80 ? 'warn' : '') }}">{{ $disk['persen'] === null ? '-' : $disk['persen'].'%' }}</div><small>sisa {{ $D::ukuran($disk['bebas']) }}</small></div>
</div>

@if($status === [])
    <div class="card"><div class="empty">Belum ada backup. Mulai dari menu <a href="{{ route('sumber.index') }}">Sumber</a>: tambahkan VM aplikasi atau Backup Master utama.</div></div>
@endif

<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Aplikasi</th><th>Database terakhir</th><th>Berkas terakhir</th><th>Uji pulih</th><th>Tersimpan</th></tr></thead>
    <tbody>
    @foreach($status as $r)
        <tr><td class="card-title"><a href="{{ route('aplikasi.show', $r['a']) }}"><b>{{ $r['a']->nama }}</b></a> <small class="mut mono">{{ $r['a']->kode }}</small>
                @foreach($r['masalah'] as $m)<br><small class="bad">⚠ {{ $m }}</small>@endforeach</td>
            <td data-label="Database">@if($r['db'])<span @class(['ok' => ! $r['masalah'], 'warn' => (bool) $r['masalah']])>●</span> {{ $D::lalu($r['db']->waktu) }}<br><small class="mut">{{ $D::ukuran($r['db']->ukuran) }} · {{ $D::waktu($r['db']->waktu) }}</small>@else<span class="mut">–</span>@endif</td>
            <td data-label="Berkas">@php($f = $r['cermin'] && (! $r['arsip'] || $r['cermin']->waktu > $r['arsip']->waktu) ? $r['cermin'] : $r['arsip'])@if($f){{ $D::lalu($f->waktu) }}<br><small class="mut">{{ \App\Models\Berkas::JENIS[$f->jenis] }}</small>@else<span class="mut">–</span>@endif</td>
            <td data-label="Uji pulih">@if($r['uji'])<span class="badge {{ ['ok' => 'b-ok', 'peringatan' => 'b-warn', 'gagal' => 'b-bad'][$r['uji']->status] }}">{{ ucfirst($r['uji']->status) }}</span><br><small class="mut">{{ $D::lalu($r['uji']->waktu) }} · {{ number_format($r['uji']->baris, 0, ',', '.') }} baris</small>@else<span class="mut">belum</span>@endif</td>
            <td data-label="Tersimpan">{{ $D::ukuran($r['ukuran']) }}<br><small class="mut">{{ $r['jumlah'] }} berkas</small></td></tr>
    @endforeach
    </tbody></table></div>

<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Penarikan terakhir</h2></div>
    @forelse($riwayat as $t)
        <div class="stu"><span class="nm"><b>{{ $t->sumber->nama ?? '(sumber dihapus)' }}</b>
            <small>{{ $D::waktu($t->mulai) }} · {{ $t->berkas_baru }} berkas baru · {{ $D::ukuran($t->bait) }}@if($t->selesai) · {{ max(1, $t->mulai->diffInSeconds($t->selesai, true)) }} dtk @endif</small>
            @if($t->pesan)<small @class(['bad' => $t->status === 'gagal', 'mut' => $t->status !== 'gagal'])>{{ \Illuminate\Support\Str::limit($t->pesan, 300) }}</small>@endif</span>
            <span class="badge {{ ['ok' => 'b-ok', 'gagal' => 'b-bad'][$t->status] ?? 'b-warn' }}">{{ $t->status === 'jalan' ? 'Berjalan' : ucfirst($t->status) }}</span></div>
    @empty
        <div class="empty">Belum ada penarikan.</div>
    @endforelse
</div>
@endsection
