<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<a class="back-link" href="<?php echo e(route('beranda')); ?>">‹ Ringkasan</a>
<div class="head"><div><h1><?php echo e($a->nama); ?></h1><small class="mono"><?php echo e($a->kode); ?></small></div>
    <div class="row"><a class="btn" href="<?php echo e(route('jelajah.aplikasi', $a)); ?>">🔎 Jelajah data</a>
        <form method="post" action="<?php echo e(route('aplikasi.uji', $a)); ?>"><?php echo csrf_field(); ?><button class="btn btn-pri">🧪 Uji pulih sekarang</button></form></div></div>

<nav class="seg" style="margin-bottom:12px">
    <a href="<?php echo e(route('aplikasi.show', $a)); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['on' => ! $jenis]); ?>">Semua</a>
    <?php $__currentLoopData = \App\Models\Berkas::JENIS; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a href="<?php echo e(route('aplikasi.show', [$a, 'jenis' => $k])); ?>" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['on' => $jenis === $k]); ?>"><?php echo e($l); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</nav>
<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Berkas</th><th>Waktu backup</th><th>Ukuran</th><th>Keutuhan</th><th></th></tr></thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $berkas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr><td class="card-title"><a href="<?php echo e(route('berkas.show', $b)); ?>"><?php echo e($b->nama); ?></a><br><small class="mut"><?php echo e(\App\Models\Berkas::JENIS[$b->jenis]); ?></small></td>
            <td data-label="Waktu"><?php echo e($D::waktu($b->waktu)); ?></td><td data-label="Ukuran"><?php echo e($D::ukuran($b->ukuran)); ?></td>
            <td data-label="Utuh"><?php if($b->utuh === false): ?><span class="badge b-bad" title="<?php echo e($b->pesan); ?>">Rusak</span><?php elseif($b->pesan): ?><span class="badge b-warn" title="<?php echo e($b->pesan); ?>">Cek</span><?php else: ?><span class="badge b-ok">Utuh</span><?php endif; ?></td>
            <td class="r"><div class="row"><?php if($b->jenis !== 'cermin'): ?><a class="btn btn-sm" href="<?php echo e(route('berkas.unduh', $b)); ?>">⬇</a><?php endif; ?><a class="btn btn-sm" href="<?php echo e(route('berkas.show', $b)); ?>">Buka</a></div></td></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="5"><div class="empty">Belum ada berkas.</div></td></tr>
    <?php endif; ?>
    </tbody></table>
    <?php echo $__env->make('partials.pager', ['p' => $berkas], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>

<div class="grid g2">
<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Uji pulih</h2></div>
    <?php $__empty_1 = true; $__currentLoopData = $uji; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="stu"><span class="nm"><b><?php echo e($D::waktu($u->waktu)); ?></b><small><?php echo e($u->berkas->nama ?? '(berkas sudah dihapus retensi)'); ?> · <?php echo e($u->tabel); ?> tabel · <?php echo e(number_format($u->baris, 0, ',', '.')); ?> baris</small>
            <?php if($u->pesan): ?><small class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bad' => $u->status === 'gagal', 'warn' => $u->status === 'peringatan']); ?>"><?php echo e($u->pesan); ?></small><?php endif; ?></span>
            <span class="badge <?php echo e(['ok' => 'b-ok', 'peringatan' => 'b-warn', 'gagal' => 'b-bad'][$u->status]); ?>"><?php echo e(ucfirst($u->status)); ?></span></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty">Belum pernah diuji.</div>
    <?php endif; ?>
</div>
<form method="post" action="<?php echo e(route('aplikasi.update', $a)); ?>" class="card"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
    <h2>Pengaturan aplikasi</h2>
    <div class="field"><label>Nama</label><input type="text" name="nama" value="<?php echo e($a->nama); ?>" required></div>
    <div class="field"><label>Tabel rahasia (pisahkan koma)</label><input type="text" name="rahasia" value="<?php echo e(implode(', ', $a->rahasia ?? [])); ?>" placeholder="bk_catatan"><small>Dibuka hanya dengan alasan tertulis, dan tercatat di Log.</small></div>
    <div class="grid g2">
        <div class="field"><label>Folder aplikasi di VM</label><input type="text" name="app_dir" value="<?php echo e($a->app_dir); ?>" placeholder="/var/www/tu-pusat"></div>
        <div class="field"><label>Nama database di VM</label><input type="text" name="db" value="<?php echo e($a->db); ?>" placeholder="tu_pusat"></div>
    </div>
    <small>Dipakai untuk menyusun langkah pemulihan.</small><br>
    <button class="btn" style="margin-top:8px">Simpan</button>
</form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $a->nama], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/aplikasi/show.blade.php ENDPATH**/ ?>