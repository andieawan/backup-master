@extends('layouts.app', ['title' => 'Jelajah '.$a->nama])
@section('content')
<a class="back-link" href="{{ route('jelajah.index') }}">‹ Jelajah Data</a>
<div class="head"><div><h1>{{ $a->nama }}</h1><small>{{ count($tabel) }} tabel</small></div></div>
@include('jelajah._kepala')
<div class="card card-tight">
@foreach($tabel as $t => $n)
    <a class="stu" style="color:inherit" href="{{ route('jelajah.tabel', [$a, $t]) }}"><span class="nm"><b class="mono">{{ $t }}</b></span>
        @if($a->tabelRahasia($t))<span class="badge b-warn">🔒 Rahasia</span>@endif<span class="badge">{{ number_format($n, 0, ',', '.') }} baris</span></a>
@endforeach
</div>
@endsection
