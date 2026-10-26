<?php ($s ??= null); ?> <?php ($t ??= []); ?>
<?php ($jenis = old('jenis', $s->jenis ?? ($t['jenis'] ?? 'aplikasi'))); ?>
<div class="grid g3">
    <div class="field"><label>Jenis</label><?php echo $__env->make('partials.select', ['name' => 'jenis', 'options' => \App\Models\Sumber::JENIS, 'selected' => $jenis], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?></div>
    <div class="field"><label>Kode</label><input type="text" name="kode" value="<?php echo e(old('kode', $s->kode ?? ($t['kode'] ?? ''))); ?>" required pattern="[a-z0-9][a-z0-9_\-]{0,39}" placeholder="tu / bk / utama"><small>Untuk VM aplikasi = kode aplikasi (nama folder penyimpanan).</small></div>
    <div class="field"><label>Nama</label><input type="text" name="nama" value="<?php echo e(old('nama', $s->nama ?? ($t['nama'] ?? ''))); ?>" required></div>
</div>
<div class="grid g3">
    <div class="field"><label>Host / IP</label><input type="text" name="host" value="<?php echo e(old('host', $s->host ?? '')); ?>" placeholder="192.168.10.21 (kosong = folder di mesin ini)"></div>
    <div class="field"><label>Port SSH</label><input type="number" name="port" value="<?php echo e(old('port', $s->port ?? 22)); ?>" min="1" max="65535"></div>
    <div class="field"><label>User SSH</label><input type="text" name="pengguna" value="<?php echo e(old('pengguna', $s->pengguna ?? 'bmtarik')); ?>"></div>
</div>
<div class="grid g3">
    <div class="field"><label>Folder di sumber</label><input type="text" name="folder" value="<?php echo e(old('folder', $s->folder ?? ($t['folder'] ?? ''))); ?>" placeholder="/var/backups/tu-pusat"><small>Dibuka baca-saja oleh skrip siapkan-sumber.sh.</small></div>
    <div class="field"><label>Subfolder arsip</label><input type="text" name="arsip" value="<?php echo e(old('arsip', $s->arsip ?? ($t['arsip'] ?? '.'))); ?>" placeholder=". (= folder itu sendiri)"><small>Berkas backup bertanggal (db_*.sql.gz, berkas_*.tar.gz). Hanya untuk VM aplikasi.</small></div>
    <div class="field"><label>Subfolder potret (opsional)</label><input type="text" name="cermin" value="<?php echo e(old('cermin', $s->cermin ?? ($t['cermin'] ?? ''))); ?>" placeholder="mis. private"><small>Folder yang disalin utuh setiap hari.</small></div>
</div>
<div class="grid g3">
    <div class="field"><label>Tarik setiap hari setelah jam</label><input type="time" name="jam" value="<?php echo e(old('jam', $s->jam ?? '02:30')); ?>"><small>Backup di VM aplikasi dibuat 01:00–02:00; Absensi 02:00.</small></div>
    <div class="field"><label class="chk"><input type="checkbox" name="aktif" value="1" <?php if(old('aktif', $s->aktif ?? true)): echo 'checked'; endif; ?>> Aktif</label></div>
</div>
<?php /**PATH /home/claude/backup-master/resources/views/sumber/_form.blade.php ENDPATH**/ ?>