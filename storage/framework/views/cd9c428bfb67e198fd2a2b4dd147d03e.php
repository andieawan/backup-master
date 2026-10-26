<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<div class="head"><div><h1>Jelajah Data</h1><small>Isi database semua aplikasi, dari backup terakhir yang lolos uji pulih. Baca saja; kolom password/token selalu disembunyikan.</small></div></div>
<div class="quick">
<?php $__empty_1 = true; $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <?php ($u = $a->ujiTerakhir); ?>
    <a class="quick-item" href="<?php echo e(route('jelajah.aplikasi', $a)); ?>"><span class="bk-ic" aria-hidden="true">🗄</span>
        <span><b><?php echo e($a->nama); ?></b><small><?php if($u && $u->status !== 'gagal'): ?><?php echo e($u->tabel); ?> tabel · backup <?php echo e($D::waktu($u->berkas?->waktu)); ?><?php elseif($u): ?>Uji pulih gagal <?php else: ?> Belum diuji <?php endif; ?></small></span></a>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="card"><div class="empty">Belum ada aplikasi.</div></div>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Jelajah Data'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/jelajah/index.blade.php ENDPATH**/ ?>