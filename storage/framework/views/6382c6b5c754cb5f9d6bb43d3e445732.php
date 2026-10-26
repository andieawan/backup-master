
<select name="<?php echo e($name); ?>" <?php if(isset($id)): ?> id="<?php echo e($id); ?>" <?php endif; ?> <?php echo $attrs ?? ''; ?>>
    <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $val => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($val); ?>" <?php if((string) $val === (string) ($selected ?? '')): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
<?php /**PATH /home/claude/backup-master/resources/views/partials/select.blade.php ENDPATH**/ ?>