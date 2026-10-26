@extends('layouts.app', ['title' => $s->nama])
@section('content')
@php($D = \App\Support\Dates::class)
@php($folder = $s->jenis === 'master' ? ($s->folder !== '/' ? $s->folder : config('bm.data_dir')) : $s->folder)
<a class="back-link" href="{{ route('sumber.index') }}">‹ Sumber</a>
<div class="head"><div><h1>{{ $s->nama }}</h1><small>{{ \App\Models\Sumber::JENIS[$s->jenis] }} · {{ $s->alamat() }} · berhasil {{ $D::lalu($s->terakhir_berhasil) }}</small></div>
    <form method="post" action="{{ route('sumber.tarik', $s) }}">@csrf<button class="btn btn-pri" @disabled(! $s->aktif)>⟳ Tarik sekarang</button></form></div>

@if($s->terakhir_status === 'gagal')<div class="alert alert-error"><span class="alert-txt"><b>Terakhir gagal:</b> {{ $s->terakhir_pesan }}</span></div>@endif
@if($s->perlu_tarik)<div class="alert alert-info"><span class="alert-txt">Menunggu giliran ditarik (±1 menit). Muat ulang halaman.</span></div>@endif

@unless($s->lokal())
<div class="card">
    <h2>Pasang akses di sumber (sekali saja)</h2>
    <p>Jalankan di <b>{{ $s->jenis === 'master' ? 'Backup Master utama' : 'VM '.$s->nama }}</b> ({{ $s->host }}). Skrip membuat user <code>{{ $s->pengguna }}</code> yang
        <b>hanya bisa membaca</b> folder <code>{{ $folder }}</code> lewat rsync — tidak bisa login, tidak bisa menulis/menghapus.</p>
@if($kunci)
<pre class="mono" style="white-space:pre-wrap;word-break:break-all;font-size:12.5px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;user-select:all">curl -fsSL https://raw.githubusercontent.com/andieawan/backup-master/main/deploy/siapkan-sumber.sh -o siapkan-sumber.sh
sudo bash siapkan-sumber.sh --user {{ $s->pengguna }} --folder {{ $folder }} --kunci '{{ $kunci }}'</pre>
    <small>Folder data VM aplikasi biasanya hanya bisa dibaca root; skrip juga memberi izin baca untuk grup <code>{{ $s->pengguna }}</code>.@if($s->port !== 22) SSH di port {{ $s->port }}.@endif</small>
@else
    <p class="bad">Kunci SSH Backup Master belum ada.</p>
@endif
</div>
@endunless

<details class="card"><summary><b>Ubah sumber</b></summary>
    <form method="post" action="{{ route('sumber.update', $s) }}" style="margin-top:12px">@csrf @method('PUT') @include('sumber._form', ['s' => $s])
        <button class="btn btn-pri">Simpan</button></form>
    <form method="post" action="{{ route('sumber.destroy', $s) }}" style="margin-top:12px" data-confirm="Hapus sumber ini? Berkas yang sudah tertarik tetap disimpan.">@csrf @method('DELETE')<button class="btn danger">Hapus sumber</button></form>
</details>

<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Riwayat</h2></div>
    @forelse($riwayat as $t)
        <div class="stu"><span class="nm"><b>{{ $D::waktu($t->mulai) }}</b><small>{{ $t->berkas_baru }} berkas baru · {{ $D::ukuran($t->bait) }}</small>
            @if($t->pesan)<small @class(['bad' => $t->status === 'gagal', 'mut' => $t->status !== 'gagal'])>{{ $t->pesan }}</small>@endif</span>
            <span class="badge {{ ['ok' => 'b-ok', 'gagal' => 'b-bad'][$t->status] ?? 'b-warn' }}">{{ $t->status === 'jalan' ? 'Berjalan' : ucfirst($t->status) }}</span></div>
    @empty
        <div class="empty">Belum pernah ditarik.</div>
    @endforelse
</div>
@endsection
