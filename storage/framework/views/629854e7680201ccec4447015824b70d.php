<?php $__env->startSection('content'); ?>
<a class="back-link" href="<?php echo e(route('jelajah.tabel', [$a, $tabel])); ?>">‹ <?php echo e($tabel); ?></a>
<div class="head"><div><h1 class="mono"><?php echo e($tabel); ?> #<?php echo e($r['id'] ?? ''); ?></h1><small><?php echo e($a->nama); ?></small></div></div>
<div class="card card-tight"><table class="tbl">
    <?php $__currentLoopData = $r; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr><th style="width:220px" class="mono"><?php echo e($k); ?></th><td style="white-space:pre-wrap;word-break:break-word"><?php if($v === null): ?><span class="mut">∅</span><?php else: ?><?php echo e($v); ?><?php endif; ?></td></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</table></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $tabel.' #'.($r['id'] ?? '')], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/jelajah/baris.blade.php ENDPATH**/ ?>