@extends('layouts.app', ['title' => 'Profil '.$jenis, 'width' => '1300px'])
@section('content')
@php($nama = $p['induk']['data']['nama'] ?? null)
<a class="back-link" href="{{ url()->previous() !== url()->current() ? url()->previous() : route('cari') }}">‹ Kembali</a>
<div class="head"><div><h1>{{ $nama ?? 'Profil '.$jenis }}</h1><small>Profil {{ $jenis }} lintas aplikasi · UUID Pusat <span class="mono">{{ $uuid }}</span> · salinan hasil uji pulih, baca saja</small></div></div>

<div class="card">
    <h2>Data induk (Pusat)</h2>
    @if($p['induk'])
        <table class="tbl" style="font-size:13.5px"><tbody>
        @foreach($p['induk']['data'] as $k => $v)<tr><td class="mut" style="width:200px">{{ $k }}</td><td>{{ $v === null || $v === '' ? '–' : $v }}</td></tr>@endforeach
        </tbody></table>
        @if($p['induk']['id'])<p style="margin:8px 0 0"><a href="{{ route('jelajah.baris', [$p['induk']['a'], $p['induk']['tabel'], $p['induk']['id']]) }}">Semua kolom di Jelajah ›</a></p>@endif
    @else
        <div class="empty">{{ $p['pusat'] ? 'Tidak ada di salinan Pusat (mungkin sudah dihapus di Pusat).' : 'Salinan Pusat belum tersedia — jalankan uji pulih Pusat.' }}</div>
    @endif
</div>

@foreach($p['aplikasi'] as $ap)
    <div class="card">
        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap"><h2 style="margin:0">{{ $ap['a']->nama }}</h2>
            @if(! $ap['ada'])<span class="badge">tidak terdaftar di aplikasi ini</span>
            @elseif($ap['id'])<a style="margin-left:auto" href="{{ route('jelajah.baris', [$ap['a'], $ap['tabel'], $ap['id']]) }}">Data {{ $jenis }} di {{ $ap['a']->nama }} ›</a>@endif</div>
        @foreach($ap['bagian'] as $b)
            <h3 style="margin:14px 0 6px;font-size:14px">{{ $b['judul'] }} <span class="badge">{{ number_format($b['total'], 0, ',', '.') }}</span>@if($b['rahasia']) <span class="badge b-warn">🔒 rahasia</span>@endif</h3>
            @if($b['rahasia'])
                <p class="mut" style="margin:0">Isinya rahasia. <a href="{{ route('jelajah.tabel', [$ap['a'], $b['tabel']]) }}">Buka dengan alasan</a> (30 menit), lalu muat ulang halaman ini.</p>
            @elseif($b['total'])
                <div class="scroll"><table class="tbl" style="font-size:13px">
                    <thead><tr>@foreach($b['kolom'] as $k)<th>{{ $k }}</th>@endforeach</tr></thead>
                    <tbody>@foreach($b['baris'] as $r)<tr>@foreach($b['kolom'] as $k)<td>{{ \Illuminate\Support\Str::limit((string) ($r[$k] ?? ''), 80) }}</td>@endforeach</tr>@endforeach</tbody>
                </table></div>
                @if($b['total'] > count($b['baris']))<p class="mut" style="margin:6px 0 0;font-size:12.5px">{{ count($b['baris']) }} terbaru dari {{ $b['total'] }}. Selengkapnya di <a href="{{ route('jelajah.tabel', [$ap['a'], $b['tabel']]) }}">Jelajah</a>.</p>@endif
            @endif
        @endforeach
        @if($ap['ada'] && ! $ap['bagian'])<p class="mut" style="margin:8px 0 0">Tidak ada data terkait yang dicatat.</p>@endif
    </div>
@endforeach
@endsection
