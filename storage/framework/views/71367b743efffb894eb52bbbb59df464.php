<?php $__env->startSection('content'); ?>
<a class="back-link" href="<?php echo e(route('jelajah.aplikasi', $a)); ?>">‹ <?php echo e($a->nama); ?></a>
<form method="post" action="<?php echo e(route('jelajah.alasan', [$a, $tabel])); ?>" class="card" style="max-width:640px"><?php echo csrf_field(); ?>
    <h1>🔒 <?php echo e($tabel); ?></h1>
    <p>Tabel ini berisi data rahasia (mis. catatan konseling). Tulis alasan membukanya; alasan, nama Anda, dan waktunya tercatat di Log. Izin berlaku 30 menit.</p>
    <div class="field"><label for="al">Alasan</label><textarea id="al" name="alasan" rows="3" required minlength="10" maxlength="500" placeholder="mis. memeriksa hasil pemulihan setelah server BK rusak"></textarea></div>
    <button class="btn btn-pri">Buka</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Data rahasia'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/jelajah/alasan.blade.php ENDPATH**/ ?>