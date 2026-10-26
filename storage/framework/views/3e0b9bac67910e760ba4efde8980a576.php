<?php $__env->startSection('content'); ?>
<?php ($u = fn ($o) => route('jelajah.tabel', [$a, $tabel] + array_filter(array_merge(['q' => $q, 'urut' => $d['urut'], 'arah' => $d['turun'] ? null : 'naik'], $o), fn ($v) => $v !== null && $v !== ''))); ?>
<?php ($hal = (int) ceil($d['total'] / $d['per'])); ?>
<a class="back-link" href="<?php echo e(route('jelajah.aplikasi', $a)); ?>">‹ <?php echo e($a->nama); ?></a>
<div class="head"><div><h1 class="mono"><?php echo e($tabel); ?></h1><small><?php echo e(number_format($d['total'], 0, ',', '.')); ?> baris<?php echo e($q !== '' ? ' cocok' : ''); ?><?php if($a->tabelRahasia($tabel)): ?> · 🔒 rahasia <?php endif; ?></small></div></div>
<?php echo $__env->make('jelajah._kepala', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<form method="get" class="card filter-card"><div class="fields">
    <div class="field"><label for="q">Cari di semua kolom</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>" placeholder="nama, NIS, tanggal…"></div>
    <div class="field"><button class="btn">Cari</button></div></div></form>
<div class="card card-tight scroll"><table class="tbl" style="font-size:13px">
    <thead><tr><?php $__currentLoopData = $d['kolom']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th><a href="<?php echo e($u(['urut' => $k, 'arah' => $d['urut'] === $k && $d['turun'] ? 'naik' : null, 'hal' => null])); ?>" style="color:inherit"><?php echo e($k); ?><?php echo e($d['urut'] === $k ? ($d['turun'] ? ' ↓' : ' ↑') : ''); ?></a></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr></thead>
    <tbody>
    <?php $__empty_1 = true; $__currentLoopData = $d['baris']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <tr><?php $__currentLoopData = $d['kolom']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($v = $r->{$k}); ?>
            <td><?php if($loop->first && $adaId): ?><a href="<?php echo e(route('jelajah.baris', [$a, $tabel, $r->id])); ?>"><?php echo e($v); ?></a><?php elseif($v === null): ?><span class="mut">∅</span><?php else: ?><?php echo e(\Illuminate\Support\Str::limit((string) $v, 80)); ?><?php endif; ?></td>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <tr><td colspan="<?php echo e(count($d['kolom'])); ?>"><div class="empty">Tidak ada baris.</div></td></tr>
    <?php endif; ?>
    </tbody></table></div>
<?php if($hal > 1): ?>
<nav class="pager"><?php if($d['halaman'] > 1): ?><a href="<?php echo e($u(['hal' => $d['halaman'] - 1])); ?>">‹ Sebelumnya</a><?php endif; ?><span class="on"><?php echo e($d['halaman']); ?> / <?php echo e($hal); ?></span><?php if($d['halaman'] < $hal): ?><a href="<?php echo e($u(['hal' => $d['halaman'] + 1])); ?>">Berikutnya ›</a><?php endif; ?></nav>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $tabel.' · '.$a->nama, 'width' => '1500px'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/jelajah/tabel.blade.php ENDPATH**/ ?>