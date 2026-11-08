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
    // User DB KHUSUS uji pulih & Jelajah Data: hanya berhak atas bmj_*, TIDAK atas database Backup Master sendiri.
    // Isi dump berasal dari VM aplikasi; bila VM itu diretas, dump-nya tidak boleh bisa menyentuh akun/katalog di sini.
    'uji_db_username' => env('BM_UJI_DB_USERNAME', ''),
    'uji_db_password' => env('BM_UJI_DB_PASSWORD', ''),
    'mysql_bin' => env('BM_MYSQL_BIN', 'mysql'),

    // Notifikasi Telegram (opsional): gagal tarik, backup terlalu tua, uji pulih gagal; plus ringkasan harian.
    'telegram_token' => env('BM_TELEGRAM_TOKEN', ''),
    'telegram_chat' => env('BM_TELEGRAM_CHAT', ''),

    // API untuk WOL Panel (status backup per aplikasi + meneruskan notifikasi ke Telegram di atas).
    // Kosong = API mati. api_ip = IP yang boleh memanggil (pisah koma), mis. 192.168.10.13 (WOL Panel); kosong = semua IP.
    'api_kunci' => env('BM_API_KUNCI', ''),
    'api_ip' => env('BM_API_IP', ''),

    // Verifikasi dua langkah (aplikasi authenticator) wajib untuk semua admin.
    'wajib_2fa' => (bool) env('BM_WAJIB_2FA', true),

    // Kolom yang SELALU disembunyikan di Jelajah Data (pola regex, nama kolom).
    'kolom_rahasia' => '/(password|passwd|remember_token|secret|token|api_key|_key|kunci|two_factor|otp|hash)$/i',
    // Tabel teknis yang tidak ditampilkan.
    'tabel_abaikan' => ['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens', 'sso_kode', 'sso_token', 'login_attempts'],

    /*
     * Master App — Cari di semua aplikasi: tabel => kolom yang dicari (di salinan database hasil uji pulih).
     * Kolom/tabel yang tidak ada di versi database tertentu dilewati. Tabel rahasia (lihat templat) hanya dihitung.
     */
    'cari' => [
        'pusat' => ['siswa' => ['nama', 'nis', 'nisn'], 'pegawai' => ['nama', 'nip'], 'users' => ['nama', 'username', 'email']],
        'jurnal' => ['students' => ['nama', 'nis'], 'users' => ['nama', 'username'], 'jurnal' => ['materi', 'kegiatan', 'catatan']],
        'bk' => ['siswa' => ['nama', 'nis', 'nisn'], 'bk_catatan' => ['judul', 'uraian', 'tindak_lanjut']],
        'tu' => ['siswa' => ['nama', 'nis', 'nisn'], 'pegawai' => ['nama', 'nip'], 'tu_surat' => ['nomor', 'pihak', 'perihal']],
        'pkl' => ['siswa' => ['nama', 'nis', 'nisn'], 'pegawai' => ['nama', 'nip'], 'dudi' => ['nama', 'alamat', 'bidang']],
        'tanggungan' => ['siswa' => ['nama', 'nis', 'nisn'], 'pembayaran' => ['nomor', 'penerima', 'catatan']],
        'web' => ['berita' => ['judul', 'isi'], 'pengumuman' => ['judul', 'isi'], 'agenda' => ['judul'], 'staf' => ['nama', 'jabatan']],
    ],

    /*
     * Master App — Profil siswa & guru lintas aplikasi, dikaitkan lewat UUID Pusat.
     * tabel/kunci = baris orang itu di aplikasi tsb; bagian = data terkait: fk menunjuk id orang itu,
     * via = [tabel perantara, kolom ke id orang] (fk lalu menunjuk id perantara), ambil = [tabel, kolom fk, [kolom]] untuk label.
     */
    'profil' => [
        'siswa' => [
            'pusat' => ['tabel' => 'siswa', 'kunci' => 'uuid', 'tampil' => ['nis', 'nisn', 'nama', 'jenis_kelamin', 'status', 'tempat_lahir', 'tanggal_lahir', 'no_hp', 'no_hp_ortu']],
            'jurnal' => ['tabel' => 'students', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Absensi', 'tabel' => 'attendance', 'fk' => 'student_id', 'kolom' => ['tanggal', 'status', 'notes'], 'urut' => 'tanggal'],
                ['judul' => 'Nilai', 'tabel' => 'grade_values', 'fk' => 'student_id', 'kolom' => ['nilai'], 'ambil' => ['grade_activities', 'activity_id', ['tanggal_kegiatan', 'nama_kegiatan']]],
            ]],
            'bk' => ['tabel' => 'siswa', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Catatan BK', 'tabel' => 'bk_catatan', 'fk' => 'siswa_id', 'kolom' => ['tanggal', 'jenis', 'kategori', 'judul', 'status'], 'urut' => 'tanggal'],
            ]],
            'tu' => ['tabel' => 'siswa', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Mutasi', 'tabel' => 'mutasi_siswa', 'fk' => 'siswa_id', 'kolom' => ['tanggal', 'jenis', 'sekolah', 'alasan', 'nomor_surat'], 'urut' => 'tanggal'],
                ['judul' => 'Surat', 'tabel' => 'tu_surat', 'fk' => 'siswa_id', 'kolom' => ['tanggal', 'nomor', 'perihal', 'status'], 'urut' => 'tanggal'],
            ]],
            'pkl' => ['tabel' => 'siswa', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Penempatan PKL', 'tabel' => 'penempatan', 'fk' => 'siswa_id', 'kolom' => ['mulai', 'selesai', 'status'], 'ambil' => ['dudi', 'dudi_id', ['nama']], 'urut' => 'mulai'],
                ['judul' => 'Absensi PKL', 'tabel' => 'absensi', 'via' => ['penempatan', 'siswa_id'], 'fk' => 'penempatan_id', 'kolom' => ['tanggal', 'status', 'jam_masuk', 'jam_pulang'], 'urut' => 'tanggal'],
                ['judul' => 'Jurnal PKL', 'tabel' => 'jurnal', 'via' => ['penempatan', 'siswa_id'], 'fk' => 'penempatan_id', 'kolom' => ['tanggal', 'kegiatan', 'status'], 'urut' => 'tanggal'],
            ]],
            'tanggungan' => ['tabel' => 'siswa', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Tagihan', 'tabel' => 'tagihan', 'fk' => 'siswa_id', 'kolom' => ['tahun_ajaran', 'jenis', 'bulan', 'nominal', 'potongan']],
                ['judul' => 'Pembayaran', 'tabel' => 'pembayaran', 'fk' => 'siswa_id', 'kolom' => ['tanggal', 'nomor', 'total', 'cara', 'batal_pada'], 'urut' => 'tanggal'],
            ]],
        ],
        'guru' => [
            'pusat' => ['tabel' => 'pegawai', 'kunci' => 'uuid', 'tampil' => ['nip', 'nama', 'jenis', 'jabatan', 'email', 'no_hp', 'aktif']],
            'jurnal' => ['tabel' => 'users', 'kunci' => 'pegawai_uuid', 'bagian' => [
                ['judul' => 'Jurnal mengajar', 'tabel' => 'jurnal', 'fk' => 'user_id', 'kolom' => ['tanggal', 'jam_ke', 'materi'], 'urut' => 'tanggal'],
                ['judul' => 'Kegiatan nilai', 'tabel' => 'grade_activities', 'fk' => 'teacher_id', 'kolom' => ['tanggal_kegiatan', 'nama_kegiatan', 'tipe_skala'], 'urut' => 'tanggal_kegiatan'],
            ]],
            'tu' => ['tabel' => 'pegawai', 'kunci' => 'pusat_uuid', 'bagian' => [
                ['judul' => 'Absen pegawai', 'tabel' => 'absen_pegawai', 'fk' => 'pegawai_id', 'kolom' => ['tanggal', 'status', 'jam_masuk', 'jam_pulang', 'terlambat'], 'urut' => 'tanggal'],
            ]],
            'pkl' => ['tabel' => 'pegawai', 'kunci' => 'pusat_uuid', 'bagian' => []],
            'tanggungan' => ['tabel' => 'pegawai', 'kunci' => 'pusat_uuid', 'bagian' => []],
        ],
    ],

    /*
     * Templat sumber. Folder = akar yang dibuka untuk Backup Master di VM sumber (dipasang deploy/siapkan-sumber.sh).
     * arsip = subfolder berisi berkas backup bertanggal; cermin = subfolder yang disalin utuh sebagai potret harian.
     * app_dir & db dipakai untuk menyusun perintah pemulihan. host & jam = usulan isian (Susunan IP sekolah 192.168.10.x).
     */
    'templat' => [
        'pusat' => ['nama' => 'Pusat Sekolah', 'host' => '192.168.10.20', 'jam' => '02:30', 'folder' => '/var/backups/pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/pusat-sekolah', 'db' => 'pusat', 'rahasia' => []],
        'web' => ['nama' => 'Website Sekolah', 'host' => '192.168.10.21', 'jam' => '02:30', 'folder' => '/var/backups/smkibu', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/web-pusat', 'db' => 'smk_ibu', 'rahasia' => []],
        // Jurnal Guru (pengganti Absensi): backup 02:00 di storage/app/backups (absensi_*.sql); potret private/ = bahan ajar & scan surat.
        'jurnal' => ['nama' => 'Jurnal Guru', 'host' => '192.168.10.22', 'jam' => '02:45', 'folder' => '/var/www/jurnal/storage/app', 'arsip' => 'backups', 'cermin' => 'private',
            'app_dir' => '/var/www/jurnal', 'db' => 'jurnal_guru', 'rahasia' => []],
        'bk' => ['nama' => 'Bimbingan Konseling', 'host' => '192.168.10.23', 'jam' => '02:30', 'folder' => '/var/backups/bk-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/bk-pusat', 'db' => 'bk_pusat', 'rahasia' => ['bk_catatan']],
        'tu' => ['nama' => 'Tata Usaha', 'host' => '192.168.10.29', 'jam' => '02:30', 'folder' => '/var/backups/tu-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/tu-pusat', 'db' => 'tu_pusat', 'rahasia' => []],
        'pkl' => ['nama' => 'Manajemen PKL', 'host' => '192.168.10.30', 'jam' => '02:30', 'folder' => '/var/backups/pkl-pusat', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/pkl-pusat', 'db' => 'pkl_pusat', 'rahasia' => []],
        'tanggungan' => ['nama' => 'Tanggungan Siswa', 'host' => '192.168.10.31', 'jam' => '02:30', 'folder' => '/var/backups/tanggungan', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/var/www/tanggungan', 'db' => 'tanggungan', 'rahasia' => []],
        // WOL Panel (container LXC): tanpa database — arsip harian wol_*.tar.gz berisi config.json (akun, komputer, kunci agen),
        // vm.json, scan.json, wallpaper, dan kunci SSH panel. tanpa_db = kesehatan dinilai dari arsip terbaru.
        'wol' => ['nama' => 'WOL Panel', 'host' => '192.168.10.13', 'jam' => '02:30', 'folder' => '/var/backups/wol-panel', 'arsip' => '.', 'cermin' => null,
            'app_dir' => '/opt/wol-panel', 'db' => null, 'rahasia' => [], 'tanpa_db' => true],
    ],
];
