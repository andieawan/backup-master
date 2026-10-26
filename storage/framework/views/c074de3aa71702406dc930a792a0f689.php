<?php if($p->hasPages()): ?>
<nav class="pager" aria-label="Halaman">
    <?php if(! $p->onFirstPage()): ?><a href="<?php echo e($p->previousPageUrl()); ?>">‹ Sebelumnya</a><?php endif; ?>
    <span class="on"><?php echo e($p->currentPage()); ?> / <?php echo e($p->lastPage()); ?></span>
    <?php if($p->hasMorePages()): ?><a href="<?php echo e($p->nextPageUrl()); ?>">Berikutnya ›</a><?php endif; ?>
    <span class="mut"><?php echo e($p->total()); ?> data</span>
</nav>
<?php endif; ?>
<?php /**PATH /home/claude/backup-master/resources/views/partials/pager.blade.php ENDPATH**/ ?>