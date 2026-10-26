<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<?php ($bermasalah = collect($status)->filter(fn ($r) => $r['masalah'])->count()); ?>
<div class="head"><div><h1>Ringkasan Backup</h1><small><?php echo e(config('bm.lokasi')); ?> · data di <code><?php echo e(config('bm.data_dir')); ?></code></small></div></div>

<div class="kpis kpis-4">
    <div class="kpi"><small>Aplikasi</small><div class="n"><?php echo e(count($status)); ?></div><small><?php echo e($bermasalah ? $bermasalah.' perlu perhatian' : 'semua sehat'); ?></small></div>
    <div class="kpi"><small>Sumber gagal</small><div class="n <?php echo e($sumber->where('terakhir_status', 'gagal')->count() ? 'bad' : 'ok'); ?>"><?php echo e($sumber->where('terakhir_status', 'gagal')->count()); ?></div><small>dari <?php echo e($sumber->where('aktif', true)->count()); ?> sumber aktif</small></div>
    <div class="kpi"><small>Total tersimpan</small><div class="n"><?php echo e($D::ukuran(collect($status)->sum('ukuran'))); ?></div><small><?php echo e(collect($status)->sum('jumlah')); ?> berkas &amp; potret</small></div>
    <div class="kpi"><small>Disk</small><div class="n <?php echo e(($disk['persen'] ?? 0) >= 90 ? 'bad' : (($disk['persen'] ?? 0) >= 80 ? 'warn' : '')); ?>"><?php echo e($disk['persen'] === null ? '-' : $disk['persen'].'%'); ?></div><small>sisa <?php echo e($D::ukuran($disk['bebas'])); ?></small></div>
</div>

<?php if($status === []): ?>
    <div class="card"><div class="empty">Belum ada backup. Mulai dari menu <a href="<?php echo e(route('sumber.index')); ?>">Sumber</a>: tambahkan VM aplikasi atau Backup Master utama.</div></div>
<?php endif; ?>

<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Aplikasi</th><th>Database terakhir</th><th>Berkas terakhir</th><th>Uji pulih</th><th>Tersimpan</th></tr></thead>
    <tbody>
    <?php $__currentLoopData = $status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr><td class="card-title"><a href="<?php echo e(route('aplikasi.show', $r['a'])); ?>"><b><?php echo e($r['a']->nama); ?></b></a> <small class="mut mono"><?php echo e($r['a']->kode); ?></small>
                <?php $__currentLoopData = $r['masalah']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><br><small class="bad">⚠ <?php echo e($m); ?></small><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></td>
            <td data-label="Database"><?php if($r['db']): ?><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['ok' => ! $r['masalah'], 'warn' => (bool) $r['masalah']]); ?>">●</span> <?php echo e($D::lalu($r['db']->waktu)); ?><br><small class="mut"><?php echo e($D::ukuran($r['db']->ukuran)); ?> · <?php echo e($D::waktu($r['db']->waktu)); ?></small><?php else: ?><span class="mut">–</span><?php endif; ?></td>
            <td data-label="Berkas"><?php ($f = $r['cermin'] && (! $r['arsip'] || $r['cermin']->waktu > $r['arsip']->waktu) ? $r['cermin'] : $r['arsip']); ?><?php if($f): ?><?php echo e($D::lalu($f->waktu)); ?><br><small class="mut"><?php echo e(\App\Models\Berkas::JENIS[$f->jenis]); ?></small><?php else: ?><span class="mut">–</span><?php endif; ?></td>
            <td data-label="Uji pulih"><?php if($r['uji']): ?><span class="badge <?php echo e(['ok' => 'b-ok', 'peringatan' => 'b-warn', 'gagal' => 'b-bad'][$r['uji']->status]); ?>"><?php echo e(ucfirst($r['uji']->status)); ?></span><br><small class="mut"><?php echo e($D::lalu($r['uji']->waktu)); ?> · <?php echo e(number_format($r['uji']->baris, 0, ',', '.')); ?> baris</small><?php else: ?><span class="mut">belum</span><?php endif; ?></td>
            <td data-label="Tersimpan"><?php echo e($D::ukuran($r['ukuran'])); ?><br><small class="mut"><?php echo e($r['jumlah']); ?> berkas</small></td></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </tbody></table></div>

<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Penarikan terakhir</h2></div>
    <?php $__empty_1 = true; $__currentLoopData = $riwayat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="stu"><span class="nm"><b><?php echo e($t->sumber->nama ?? '(sumber dihapus)'); ?></b>
            <small><?php echo e($D::waktu($t->mulai)); ?> · <?php echo e($t->berkas_baru); ?> berkas baru · <?php echo e($D::ukuran($t->bait)); ?><?php if($t->selesai): ?> · <?php echo e(max(1, $t->mulai->diffInSeconds($t->selesai, true))); ?> dtk <?php endif; ?></small>
            <?php if($t->pesan): ?><small class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bad' => $t->status === 'gagal', 'mut' => $t->status !== 'gagal']); ?>"><?php echo e(\Illuminate\Support\Str::limit($t->pesan, 300)); ?></small><?php endif; ?></span>
            <span class="badge <?php echo e(['ok' => 'b-ok', 'gagal' => 'b-bad'][$t->status] ?? 'b-warn'); ?>"><?php echo e($t->status === 'jalan' ? 'Berjalan' : ucfirst($t->status)); ?></span></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty">Belum ada penarikan.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Ringkasan'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/beranda.blade.php ENDPATH**/ ?>