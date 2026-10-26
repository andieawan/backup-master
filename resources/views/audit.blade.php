@extends('layouts.app', ['title' => 'Log'])
@section('content')
@php($D = \App\Support\Dates::class)
<div class="head"><div><h1>Log</h1><small>Login, unduhan, pembukaan data, tabel rahasia (beserta alasan), penarikan, dan uji pulih.</small></div></div>
<form method="get" class="card filter-card"><div class="fields"><div class="field"><label for="q">Cari</label><input id="q" type="search" name="q" value="{{ $q }}"></div><div class="field"><button class="btn">Cari</button></div></div></form>
<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Waktu</th><th>Aksi</th><th>Oleh</th><th>Rincian</th></tr></thead>
    <tbody>@foreach($list as $l)
        <tr><td class="card-title">{{ $D::waktu($l->waktu) }}</td><td data-label="Aksi"><b>{{ $l->aksi }}</b></td><td data-label="Oleh">{{ $l->pelaku }}@if($l->ip)<br><small class="mut mono">{{ $l->ip }}</small>@endif</td><td data-label="Rincian">{{ $l->rincian }}</td></tr>
    @endforeach</tbody></table>
    @include('partials.pager', ['p' => $list])
</div>
@endsection
