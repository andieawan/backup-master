<?php $__env->startSection('content'); ?>
<a class="back-link" href="<?php echo e(route('jelajah.index')); ?>">‹ Jelajah Data</a>
<div class="head"><div><h1><?php echo e($a->nama); ?></h1><small><?php echo e(count($tabel)); ?> tabel</small></div></div>
<?php echo $__env->make('jelajah._kepala', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<div class="card card-tight">
<?php $__currentLoopData = $tabel; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t => $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <a class="stu" style="color:inherit" href="<?php echo e(route('jelajah.tabel', [$a, $t])); ?>"><span class="nm"><b class="mono"><?php echo e($t); ?></b></span>
        <?php if($a->tabelRahasia($t)): ?><span class="badge b-warn">🔒 Rahasia</span><?php endif; ?><span class="badge"><?php echo e(number_format($n, 0, ',', '.')); ?> baris</span></a>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Jelajah '.$a->nama], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/jelajah/aplikasi.blade.php ENDPATH**/ ?>