<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<div class="head"><div><h1>Log</h1><small>Login, unduhan, pembukaan data, tabel rahasia (beserta alasan), penarikan, dan uji pulih.</small></div></div>
<form method="get" class="card filter-card"><div class="fields"><div class="field"><label for="q">Cari</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>"></div><div class="field"><button class="btn">Cari</button></div></div></form>
<div class="card card-tight scroll"><table class="tbl tbl-cards">
    <thead><tr><th>Waktu</th><th>Aksi</th><th>Oleh</th><th>Rincian</th></tr></thead>
    <tbody><?php $__currentLoopData = $list; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr><td class="card-title"><?php echo e($D::waktu($l->waktu)); ?></td><td data-label="Aksi"><b><?php echo e($l->aksi); ?></b></td><td data-label="Oleh"><?php echo e($l->pelaku); ?><?php if($l->ip): ?><br><small class="mut mono"><?php echo e($l->ip); ?></small><?php endif; ?></td><td data-label="Rincian"><?php echo e($l->rincian); ?></td></tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table>
    <?php echo $__env->make('partials.pager', ['p' => $list], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Log'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/audit.blade.php ENDPATH**/ ?>