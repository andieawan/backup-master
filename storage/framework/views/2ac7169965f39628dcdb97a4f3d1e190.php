<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<?php ($folder = $s->jenis === 'master' ? ($s->folder !== '/' ? $s->folder : config('bm.data_dir')) : $s->folder); ?>
<a class="back-link" href="<?php echo e(route('sumber.index')); ?>">‹ Sumber</a>
<div class="head"><div><h1><?php echo e($s->nama); ?></h1><small><?php echo e(\App\Models\Sumber::JENIS[$s->jenis]); ?> · <?php echo e($s->alamat()); ?> · berhasil <?php echo e($D::lalu($s->terakhir_berhasil)); ?></small></div>
    <form method="post" action="<?php echo e(route('sumber.tarik', $s)); ?>"><?php echo csrf_field(); ?><button class="btn btn-pri" <?php if(! $s->aktif): echo 'disabled'; endif; ?>>⟳ Tarik sekarang</button></form></div>

<?php if($s->terakhir_status === 'gagal'): ?><div class="alert alert-error"><span class="alert-txt"><b>Terakhir gagal:</b> <?php echo e($s->terakhir_pesan); ?></span></div><?php endif; ?>
<?php if($s->perlu_tarik): ?><div class="alert alert-info"><span class="alert-txt">Menunggu giliran ditarik (±1 menit). Muat ulang halaman.</span></div><?php endif; ?>

<?php if (! ($s->lokal())): ?>
<div class="card">
    <h2>Pasang akses di sumber (sekali saja)</h2>
    <p>Jalankan di <b><?php echo e($s->jenis === 'master' ? 'Backup Master utama' : 'VM '.$s->nama); ?></b> (<?php echo e($s->host); ?>). Skrip membuat user <code><?php echo e($s->pengguna); ?></code> yang
        <b>hanya bisa membaca</b> folder <code><?php echo e($folder); ?></code> lewat rsync — tidak bisa login, tidak bisa menulis/menghapus.</p>
<?php if($kunci): ?>
<pre class="mono" style="white-space:pre-wrap;word-break:break-all;font-size:12.5px;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:10px;user-select:all">curl -fsSL https://raw.githubusercontent.com/andieawan/backup-master/main/deploy/siapkan-sumber.sh -o siapkan-sumber.sh
sudo bash siapkan-sumber.sh --user <?php echo e($s->pengguna); ?> --folder <?php echo e($folder); ?> --kunci '<?php echo e($kunci); ?>'</pre>
    <small>Folder data VM aplikasi biasanya hanya bisa dibaca root; skrip juga memberi izin baca untuk grup <code><?php echo e($s->pengguna); ?></code>.<?php if($s->port !== 22): ?> SSH di port <?php echo e($s->port); ?>.<?php endif; ?></small>
<?php else: ?>
    <p class="bad">Kunci SSH Backup Master belum ada.</p>
<?php endif; ?>
</div>
<?php endif; ?>

<details class="card"><summary><b>Ubah sumber</b></summary>
    <form method="post" action="<?php echo e(route('sumber.update', $s)); ?>" style="margin-top:12px"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?> <?php echo $__env->make('sumber._form', ['s' => $s], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <button class="btn btn-pri">Simpan</button></form>
    <form method="post" action="<?php echo e(route('sumber.destroy', $s)); ?>" style="margin-top:12px" data-confirm="Hapus sumber ini? Berkas yang sudah tertarik tetap disimpan."><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn danger">Hapus sumber</button></form>
</details>

<div class="card card-tight">
    <div style="padding:14px 16px;border-bottom:1px solid var(--line2)"><h2>Riwayat</h2></div>
    <?php $__empty_1 = true; $__currentLoopData = $riwayat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="stu"><span class="nm"><b><?php echo e($D::waktu($t->mulai)); ?></b><small><?php echo e($t->berkas_baru); ?> berkas baru · <?php echo e($D::ukuran($t->bait)); ?></small>
            <?php if($t->pesan): ?><small class="<?php echo \Illuminate\Support\Arr::toCssClasses(['bad' => $t->status === 'gagal', 'mut' => $t->status !== 'gagal']); ?>"><?php echo e($t->pesan); ?></small><?php endif; ?></span>
            <span class="badge <?php echo e(['ok' => 'b-ok', 'gagal' => 'b-bad'][$t->status] ?? 'b-warn'); ?>"><?php echo e($t->status === 'jalan' ? 'Berjalan' : ucfirst($t->status)); ?></span></div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="empty">Belum pernah ditarik.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $s->nama], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/sumber/show.blade.php ENDPATH**/ ?>