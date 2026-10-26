@extends('layouts.app', ['title' => $tabel.' #'.($r['id'] ?? '')])
@section('content')
<a class="back-link" href="{{ route('jelajah.tabel', [$a, $tabel]) }}">‹ {{ $tabel }}</a>
<div class="head"><div><h1 class="mono">{{ $tabel }} #{{ $r['id'] ?? '' }}</h1><small>{{ $a->nama }}</small></div></div>
<div class="card card-tight"><table class="tbl">
    @foreach($r as $k => $v)
        <tr><th style="width:220px" class="mono">{{ $k }}</th><td style="white-space:pre-wrap;word-break:break-word">@if($v === null)<span class="mut">∅</span>@else{{ $v }}@endif</td></tr>
    @endforeach
</table></div>
@endsection
