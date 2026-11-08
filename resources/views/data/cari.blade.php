@extends('layouts.app', ['title' => $q !== '' ? 'Cari: '.$q : 'Cari', 'width' => '1300px'])
@section('content')
<div class="head"><div><h1>Cari di semua aplikasi</h1><small>Salinan database Pusat, Jurnal, BK, TU, PKL, Tanggungan &amp; Website (hasil uji pulih terakhir, baca saja)</small></div></div>
<form method="get" class="card filter-card"><div class="fields">
    <div class="field" style="flex:1"><label for="q">Nama, NIS, NIP, nomor surat, atau kata</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="mis. Budi, 1001, 421.3/…" autofocus minlength="2"></div>
    <div class="field"><button class="btn">Cari</button></div></div></form>

@if($q !== '' && mb_strlen($q) < 2)
    <div class="card"><div class="empty">Ketik minimal 2 huruf.</div></div>
@elseif($q !== '' && $hasil === [])
    <div class="card"><div class="empty">Tidak ditemukan di aplikasi mana pun. Aplikasi yang belum pernah lolos uji pulih tidak ikut dicari.</div></div>
@endif

@foreach($hasil as $h)
    <div class="card card-tight">
        <div style="padding:12px 16px;border-bottom:1px solid var(--line2);display:flex;gap:10px;align-items:center;flex-wrap:wrap">
            <b>{{ $h['a']->nama }}</b><span class="mono mut">{{ $h['tabel'] }}</span>
            <span class="badge">{{ number_format($h['total'], 0, ',', '.') }} cocok</span>
            @if($h['rahasia'])<span class="badge b-warn">🔒 rahasia</span>@endif
            <a style="margin-left:auto" href="{{ route('jelajah.tabel', [$h['a'], $h['tabel'], 'q' => $q]) }}">{{ $h['rahasia'] ? 'Buka (perlu alasan) ›' : 'Lihat semua di Jelajah ›' }}</a>
        </div>
        @if(! $h['rahasia'])
        <div class="scroll"><table class="tbl" style="font-size:13px">
            <thead><tr>@foreach($h['kolom'] as $k)<th>{{ $k }}</th>@endforeach<th></th></tr></thead>
            <tbody>@foreach($h['baris'] as $r)<tr>
                @foreach($h['kolom'] as $k)<td>{{ \Illuminate\Support\Str::limit((string) ($r[$k] ?? ''), 80) }}</td>@endforeach
                <td style="white-space:nowrap;text-align:right">
                    @if($h['profil'] && ! empty($r[$h['kunci']]))<a href="{{ route('profil', [$h['profil'], $r[$h['kunci']]]) }}">Profil {{ $h['profil'] }}</a> · @endif
                    @if($h['adaId'])<a href="{{ route('jelajah.baris', [$h['a'], $h['tabel'], $r['id']]) }}">Detail</a>@endif</td>
            </tr>@endforeach</tbody></table></div>
        @if($h['total'] > count($h['baris']))<div class="mut" style="padding:8px 16px;font-size:12.5px">Menampilkan {{ count($h['baris']) }} dari {{ $h['total'] }} — selengkapnya di Jelajah.</div>@endif
        @endif
    </div>
@endforeach
@endsection
