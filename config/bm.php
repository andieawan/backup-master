<?php

// Konfigurasi Backup Master.
return [
    'school_name' => env('SCHOOL_NAME', 'SMKS Islam Bustanul Ulum Pakusari'),
    'timezone' => env('BM_TIMEZONE', 'Asia/Jakarta'),

    // Nama tempat ini, tampil di judul & notifikasi (mis. "Utama (Proxmox)", "Komputer TU", "VPS").
    'lokasi' => env('BM_LOKASI', 'Backup Master'),

    // Folder penyimpanan semua backup. Taruh di disk besar (VM utama: disk di HDD-BACKUP).
    'data_dir' => env('BM_DATA_DIR', '/srv/backup-master'),

    // Kunci SSH untuk menarik backup (dibuat install.sh). Kunci publiknya dipasang di setiap sumber.
    'ssh_key' => env('BM_SSH_KEY', '/var/lib/backup-master/.ssh/id_ed25519'),
    'ssh_known_hosts' => env('BM_SSH_KNOWN_HOSTS', '/var/lib/backup-master/.ssh/known_hosts'),
    'rsync_timeout' => (int) env('BM_RSYNC_TIMEOUT', 1800),     // detik per sumber
    'rsync_bwlimit' => (int) env('BM_RSYNC_BWLIMIT', 0),        // KB/s, 0 = tanpa batas (isi untuk VPS agar internet sekolah tidak penuh)

    // Retensi bertingkat (berlaku per aplikasi): harian, mingguan, bulanan.
    'simpan_harian' => (int) env('BM_SIMPAN_HARIAN', 14),
    'simpan_mingguan' => (int) env('BM_SIMPAN_MINGGUAN', 8),
    'simpan_bulanan' => (int) env('BM_SIMPAN_BULANAN', 12),

    // Peringatan bila backup terbaru suatu aplikasi lebih tua dari ini (jam).
    'batas_umur_jam' => (int) env('BM_BATAS_UMUR_JAM', 30),
    // Peringatan uji pulih bila jumlah baris turun lebih dari persen ini dibanding uji sebelumnya.
    'batas_turun_persen' => (int) env('BM_BATAS_TURUN_PERSEN', 20),
    // Uji pulih otomatis setelah backup database baru tertarik (sekaligus menyegarkan menu Jelajah Data).
    'uji_otomatis' => (bool) env('BM_UJI_OTOMATIS', true),

    // Database pemulihan (Jelajah Data): satu database per aplikasi, bmj_<kode>. User DB perlu hak penuh pada bmj\_%.
    'jelajah_prefix' => env('BM_JELAJAH_PREFIX', 'bmj_'),
    'mysql_bin' => env('BM_MYSQL_BIN', 'mysql'),

    // Notifikasi Telegram (opsional): gagal tarik, backup terlalu tua, uji pulih gagal; plus ringkasan harian.
    'telegram_token' => env('BM_TELEGRAM_TOKEN', ''),
    'telegram_chat' => env('BM_TELEGRAM_CHAT', ''),

    // Verifikasi dua langkah (aplikasi authenticator) wajib untuk semua admin.
    'wajib_2fa' => (bool) env('BM_WAJIB_2FA', true),

    // Kolom yang SELALU disembunyikan di Jelajah Data (pola regex, nama kolom).
    'kolom_rahasia' => '/(password|passwd|remember_token|secret|token|api_key|kunci|two_factor|otp|hash)$/i',
    // Tabel teknis yang tidak ditampilkan.
    'tabel_abaikan' => ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'sso_kode', 'sso_token', 'login_attempts'],

    /*
     * Templat sumber. Folder = akar yang dibuka untuk Backup Master di VM sumber (dipasang deploy/siapkan-sumber.sh).
     * arsip = subfolder berisi berkas backup bertanggal; cermin = subfolder yang disalin utuh sebagai potret harian.
     * app_dir & db dipakai untuk menyusun perintah pemulihan.
     */
    'templat' => [
        'pusat' => ['nama' => 'Pusat Sekolah', 'folder' => '/var/backups/pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/pusat-sekolah', 'db' => 'pusat', 'rahasia' => []],
        'absensi' => ['nama' => 'Absensi Siswa', 'folder' => '/var/www/absensi/storage/app', 'arsip' => 'backups', 'cermin' => 'private',
            'app_dir' => '/var/www/absensi', 'db' => 'absensi_siswa', 'rahasia' => ['bk_records']],
        'bk' => ['nama' => 'Bimbingan Konseling', 'folder' => '/var/backups/bk-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/bk-pusat', 'db' => 'bk_pusat', 'rahasia' => ['bk_catatan']],
        'tu' => ['nama' => 'Tata Usaha', 'folder' => '/var/backups/tu-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/tu-pusat', 'db' => 'tu_pusat', 'rahasia' => []],
        'pkl' => ['nama' => 'Manajemen PKL', 'folder' => '/var/backups/pkl-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/pkl-pusat', 'db' => 'pkl_pusat', 'rahasia' => []],
        'web' => ['nama' => 'Website Sekolah', 'folder' => '/var/backups/smkibu', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/web-pusat', 'db' => 'smk_ibu', 'rahasia' => []],
    ],
];
