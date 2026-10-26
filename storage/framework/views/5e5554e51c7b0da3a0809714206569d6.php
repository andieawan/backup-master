<?php $__env->startSection('content'); ?>
<?php ($D = \App\Support\Dates::class); ?>
<?php ($I = \App\Services\Backup\IsiBerkas::class); ?>
<a class="back-link" href="<?php echo e(route('aplikasi.show', $b->aplikasi)); ?>">‹ <?php echo e($a->nama ?? $b->aplikasi); ?></a>
<div class="head"><div><h1 style="word-break:break-all"><?php echo e($b->nama); ?></h1><small><?php echo e(\App\Models\Berkas::JENIS[$b->jenis]); ?> · <?php echo e($D::waktu($b->waktu)); ?> · <?php echo e($D::ukuran($b->ukuran)); ?></small></div>
    <?php if($b->jenis !== 'cermin'): ?><a class="btn btn-pri" href="<?php echo e(route('berkas.unduh', $b)); ?>">⬇ Unduh</a><?php endif; ?></div>
<?php if($b->utuh === false): ?><div class="alert alert-error"><span class="alert-txt"><b>Berkas tidak utuh:</b> <?php echo e($b->pesan); ?></span></div><?php elseif($b->pesan): ?><div class="alert alert-warn"><span class="alert-txt"><?php echo e($b->pesan); ?></span></div><?php endif; ?>
<?php if($b->sha256): ?><p class="mut"><small>SHA-256: <code class="mono" style="word-break:break-all"><?php echo e($b->sha256); ?></code></small></p><?php endif; ?>

<?php if($b->jenis === 'db'): ?>
    <div class="card"><p>Isi database ini bisa dilihat di <a href="<?php echo e(route('jelajah.aplikasi', $b->aplikasi)); ?>"><b>Jelajah Data</b></a> (memakai backup database yang terakhir diuji pulih).</p></div>
<?php elseif($b->jenis === 'arsip'): ?>
    <form method="get" class="card filter-card"><div class="fields"><div class="field"><label for="q">Cari berkas di arsip</label><input id="q" type="search" name="q" value="<?php echo e($q); ?>" placeholder="nama berkas"></div><div class="field"><button class="btn">Cari</button></div></div></form>
    <div class="card card-tight">
        <div style="padding:12px 16px;border-bottom:1px solid var(--line2)"><b><?php echo e($jumlahIsi); ?> berkas</b><?php if($jumlahIsi > 500): ?> <small class="mut">(500 pertama; persempit dengan pencarian)</small><?php endif; ?></div>
        <?php $__empty_1 = true; $__currentLoopData = $isi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $x): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <div class="stu"><span class="nm"><b style="word-break:break-all"><?php echo e($x['nama']); ?></b><small><?php echo e($D::ukuran($x['ukuran'])); ?></small></span>
                <span class="row"><?php if($I::gambar($x['nama']) || str_ends_with(strtolower($x['nama']), '.pdf')): ?><a class="btn btn-sm" target="_blank" rel="noopener" href="<?php echo e(route('berkas.isi', [$b, 'f' => $x['nama']])); ?>">Lihat</a><?php endif; ?>
                    <a class="btn btn-sm" href="<?php echo e(route('berkas.isi', [$b, 'f' => $x['nama'], 'unduh' => 1])); ?>">⬇</a></span></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="empty">Arsip kosong / tidak ada yang cocok.</div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php ($naik = $sub === '' ? null : (str_contains($sub, '/') ? substr($sub, 0, strrpos($sub, '/')) : '')); ?>
    <div class="card card-tight">
        <div style="padding:12px 16px;border-bottom:1px solid var(--line2)"><b>/<?php echo e($sub); ?></b><?php if($naik !== null): ?> · <a href="<?php echo e(route('berkas.show', [$b, 'p' => $naik])); ?>">‹ naik</a><?php endif; ?></div>
        <?php $__empty_1 = true; $__currentLoopData = $folder; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $x): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php ($p = ltrim($sub.'/'.$x['nama'], '/')); ?>
            <div class="stu"><?php if($x['dir']): ?><a class="nm" href="<?php echo e(route('berkas.show', [$b, 'p' => $p])); ?>"><b>📁 <?php echo e($x['nama']); ?></b></a>
                <?php else: ?><span class="nm"><b style="word-break:break-all"><?php echo e($x['nama']); ?></b><small><?php echo e($D::ukuran($x['ukuran'])); ?></small></span>
                    <span class="row"><?php if($I::gambar($x['nama']) || str_ends_with(strtolower($x['nama']), '.pdf')): ?><a class="btn btn-sm" target="_blank" rel="noopener" href="<?php echo e(route('berkas.isi', [$b, 'f' => $p])); ?>">Lihat</a><?php endif; ?>
                        <a class="btn btn-sm" href="<?php echo e(route('berkas.isi', [$b, 'f' => $p, 'unduh' => 1])); ?>">⬇</a></span><?php endif; ?></div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="empty">Folder kosong.</div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<details class="card"><summary><b>Langkah memulihkan ke aplikasi</b></summary>
    <div class="alert alert-warn" style="margin-top:10px"><span class="alert-txt">Pemulihan <b>menimpa data yang ada sekarang</b> di VM tujuan. Pastikan berkas yang dipilih benar. Backup Master tidak memulihkan otomatis — langkah dijalankan manual agar tidak ada salah tekan.</span></div>
    <?php $__currentLoopData = $langkah; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $l): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <p style="margin:12px 0 4px"><b><?php echo e($i + 1); ?>. <?php echo e($l['judul']); ?></b> <small class="mut">— di <?php echo e($l['di']); ?></small></p>
        <pre class="mono" style="white-space:pre-wrap;word-break:break-all;font-size:12.5px;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:10px;user-select:all"><?php echo e($l['perintah']); ?></pre>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <small class="mut">Ganti &lt;admin&gt; dengan user Anda di VM. Absensi juga punya skrip sendiri: <code>sudo bash deploy/restore.sh</code>.</small>
</details>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => $b->nama], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/claude/backup-master/resources/views/berkas/show.blade.php ENDPATH**/ ?>