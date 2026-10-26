@extends('layouts.app', ['title' => $b->nama])
@section('content')
@php($D = \App\Support\Dates::class)
@php($I = \App\Services\Backup\IsiBerkas::class)
<a class="back-link" href="{{ route('aplikasi.show', $b->aplikasi) }}">‹ {{ $a->nama ?? $b->aplikasi }}</a>
<div class="head"><div><h1 style="word-break:break-all">{{ $b->nama }}</h1><small>{{ \App\Models\Berkas::JENIS[$b->jenis] }} · {{ $D::waktu($b->waktu) }} · {{ $D::ukuran($b->ukuran) }}</small></div>
    @if($b->jenis !== 'cermin')<a class="btn btn-pri" href="{{ route('berkas.unduh', $b) }}">⬇ Unduh</a>@endif</div>
@if($b->utuh === false)<div class="alert alert-error"><span class="alert-txt"><b>Berkas tidak utuh:</b> {{ $b->pesan }}</span></div>@elseif($b->pesan)<div class="alert alert-warn"><span class="alert-txt">{{ $b->pesan }}</span></div>@endif
@if($b->sha256)<p class="mut"><small>SHA-256: <code class="mono" style="word-break:break-all">{{ $b->sha256 }}</code></small></p>@endif

@if($b->jenis === 'db')
    <div class="card"><p>Isi database ini bisa dilihat di <a href="{{ route('jelajah.aplikasi', $b->aplikasi) }}"><b>Jelajah Data</b></a> (memakai backup database yang terakhir diuji pulih).</p></div>
@elseif($b->jenis === 'arsip')
    <form method="get" class="card filter-card"><div class="fields"><div class="field"><label for="q">Cari berkas di arsip</label><input id="q" type="search" name="q" value="{{ $q }}" placeholder="nama berkas"></div><div class="field"><button class="btn">Cari</button></div></div></form>
    <div class="card card-tight">
        <div style="padding:12px 16px;border-bottom:1px solid var(--line2)"><b>{{ $jumlahIsi }} berkas</b>@if($jumlahIsi > 500) <small class="mut">(500 pertama; persempit dengan pencarian)</small>@endif</div>
        @forelse($isi as $x)
            <div class="stu"><span class="nm"><b style="word-break:break-all">{{ $x['nama'] }}</b><small>{{ $D::ukuran($x['ukuran']) }}</small></span>
                <span class="row">@if($I::gambar($x['nama']) || str_ends_with(strtolower($x['nama']), '.pdf'))<a class="btn btn-sm" target="_blank" rel="noopener" href="{{ route('berkas.isi', [$b, 'f' => $x['nama']]) }}">Lihat</a>@endif
                    <a class="btn btn-sm" href="{{ route('berkas.isi', [$b, 'f' => $x['nama'], 'unduh' => 1]) }}">⬇</a></span></div>
        @empty
            <div class="empty">Arsip kosong / tidak ada yang cocok.</div>
        @endforelse
    </div>
@else
    @php($naik = $sub === '' ? null : (str_contains($sub, '/') ? substr($sub, 0, strrpos($sub, '/')) : ''))
    <div class="card card-tight">
        <div style="padding:12px 16px;border-bottom:1px solid var(--line2)"><b>/{{ $sub }}</b>@if($naik !== null) · <a href="{{ route('berkas.show', [$b, 'p' => $naik]) }}">‹ naik</a>@endif</div>
        @forelse($folder as $x)
            @php($p = ltrim($sub.'/'.$x['nama'], '/'))
            <div class="stu">@if($x['dir'])<a class="nm" href="{{ route('berkas.show', [$b, 'p' => $p]) }}"><b>📁 {{ $x['nama'] }}</b></a>
                @else<span class="nm"><b style="word-break:break-all">{{ $x['nama'] }}</b><small>{{ $D::ukuran($x['ukuran']) }}</small></span>
                    <span class="row">@if($I::gambar($x['nama']) || str_ends_with(strtolower($x['nama']), '.pdf'))<a class="btn btn-sm" target="_blank" rel="noopener" href="{{ route('berkas.isi', [$b, 'f' => $p]) }}">Lihat</a>@endif
                        <a class="btn btn-sm" href="{{ route('berkas.isi', [$b, 'f' => $p, 'unduh' => 1]) }}">⬇</a></span>@endif</div>
        @empty
            <div class="empty">Folder kosong.</div>
        @endforelse
    </div>
@endif

<details class="card"><summary><b>Langkah memulihkan ke aplikasi</b></summary>
    <div class="alert alert-warn" style="margin-top:10px"><span class="alert-txt">Pemulihan <b>menimpa data yang ada sekarang</b> di VM tujuan. Pastikan berkas yang dipilih benar. Backup Master tidak memulihkan otomatis — langkah dijalankan manual agar tidak ada salah tekan.</span></div>
    @foreach($langkah as $i => $l)
        <p style="margin:12px 0 4px"><b>{{ $i + 1 }}. {{ $l['judul'] }}</b> <small class="mut">— di {{ $l['di'] }}</small></p>
        <pre class="mono" style="white-space:pre-wrap;word-break:break-all;font-size:12.5px;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:10px;user-select:all">{{ $l['perintah'] }}</pre>
    @endforeach
    <small class="mut">Ganti &lt;admin&gt; dengan user Anda di VM. Absensi juga punya skrip sendiri: <code>sudo bash deploy/restore.sh</code>.</small>
</details>
@endsection
