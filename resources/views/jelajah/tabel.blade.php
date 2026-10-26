@extends('layouts.app', ['title' => $tabel.' · '.$a->nama, 'width' => '1500px'])
@section('content')
@php($u = fn ($o) => route('jelajah.tabel', [$a, $tabel] + array_filter(array_merge(['q' => $q, 'urut' => $d['urut'], 'arah' => $d['turun'] ? null : 'naik'], $o), fn ($v) => $v !== null && $v !== '')))
@php($hal = (int) ceil($d['total'] / $d['per']))
<a class="back-link" href="{{ route('jelajah.aplikasi', $a) }}">‹ {{ $a->nama }}</a>
<div class="head"><div><h1 class="mono">{{ $tabel }}</h1><small>{{ number_format($d['total'], 0, ',', '.') }} baris{{ $q !== '' ? ' cocok' : '' }}@if($a->tabelRahasia($tabel)) · 🔒 rahasia @endif</small></div></div>
@include('jelajah._kepala')
<form method="get" class="card filter-card"><div class="fields">
    <div class="field"><label for="q">Cari di semua kolom</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="nama, NIS, tanggal…"></div>
    <div class="field"><button class="btn">Cari</button></div></div></form>
<div class="card card-tight scroll"><table class="tbl" style="font-size:13px">
    <thead><tr>@foreach($d['kolom'] as $k)<th><a href="{{ $u(['urut' => $k, 'arah' => $d['urut'] === $k && $d['turun'] ? 'naik' : null, 'hal' => null]) }}" style="color:inherit">{{ $k }}{{ $d['urut'] === $k ? ($d['turun'] ? ' ↓' : ' ↑') : '' }}</a></th>@endforeach</tr></thead>
    <tbody>
    @forelse($d['baris'] as $r)
        <tr>@foreach($d['kolom'] as $k)
            @php($v = $r->{$k})
            <td>@if($loop->first && $adaId)<a href="{{ route('jelajah.baris', [$a, $tabel, $r->id]) }}">{{ $v }}</a>@elseif($v === null)<span class="mut">∅</span>@else{{ \Illuminate\Support\Str::limit((string) $v, 80) }}@endif</td>
        @endforeach</tr>
    @empty
        <tr><td colspan="{{ count($d['kolom']) }}"><div class="empty">Tidak ada baris.</div></td></tr>
    @endforelse
    </tbody></table></div>
@if($hal > 1)
<nav class="pager">@if($d['halaman'] > 1)<a href="{{ $u(['hal' => $d['halaman'] - 1]) }}">‹ Sebelumnya</a>@endif<span class="on">{{ $d['halaman'] }} / {{ $hal }}</span>@if($d['halaman'] < $hal)<a href="{{ $u(['hal' => $d['halaman'] + 1]) }}">Berikutnya ›</a>@endif</nav>
@endif
@endsection
