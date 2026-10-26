<?php ($icons = ['success' => '✓', 'warning' => '!', 'error' => '✕', 'info' => 'i']); ?>
<?php $__currentLoopData = ['success' => 'success', 'warning' => 'warn', 'error' => 'error', 'info' => 'info']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cls): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if(session($key)): ?>
        <div class="alert alert-<?php echo e($cls); ?> alert-flash" role="<?php echo e($key === 'error' ? 'alert' : 'status'); ?>">
            <span class="alert-ic" aria-hidden="true"><?php echo e($icons[$key]); ?></span><span class="alert-txt"><?php echo e(session($key)); ?></span>
            <button type="button" class="alert-x" data-dismiss aria-label="Tutup pesan">×</button>
        </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if(isset($errors) && $errors->any()): ?>
    <div class="alert alert-error alert-flash" role="alert">
        <span class="alert-ic" aria-hidden="true">✕</span>
        <span class="alert-txt"><?php if($errors->count() === 1): ?><?php echo e($errors->first()); ?><?php else: ?><b>Periksa kembali isian:</b><ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $e): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($e); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul><?php endif; ?></span>
        <button type="button" class="alert-x" data-dismiss aria-label="Tutup pesan">×</button>
    </div>
<?php endif; ?>
<?php /**PATH /home/claude/backup-master/resources/views/partials/flash.blade.php ENDPATH**/ ?>