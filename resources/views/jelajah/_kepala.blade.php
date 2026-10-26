@php($D = \App\Support\Dates::class)
@if($uji)<div class="alert alert-info"><span class="alert-txt">Data per <b>{{ $D::waktu($uji->berkas?->waktu) }}</b> ({{ $uji->berkas->nama ?? '-' }}), dimuat {{ $D::lalu($uji->waktu) }}.@if($uji->status === 'peringatan') <span class="warn">⚠ {{ $uji->pesan }}</span>@endif</span></div>@endif
