# Backup Master — SMKS Islam Bustanul Ulum Pakusari

Satu tempat untuk **semua backup aplikasi sekolah** (Pusat, Absensi, BK, TU, PKL, Website):

- **Menarik** backup harian dari setiap VM lewat rsync/SSH, dengan akses baca-saja (sumber tidak bisa menulis atau menghapus di sini).
- **Memeriksa**: keutuhan berkas (gzip/sha256), umur backup, **uji pulih otomatis** ke database terpisah, perbandingan jumlah baris.
- **Menyajikan jadi satu**: dasbor status, riwayat, unduh, **Jelajah Data** (isi database semua aplikasi, baca saja), isi arsip & potret folder (foto, scan surat).
- **Bertingkat di beberapa tempat**: Utama (VM Proxmox di HDD-BACKUP) → salinan di komputer sekolah → salinan di VPS. Aplikasinya sama.
- Retensi 14 harian / 8 mingguan / 12 bulanan, notifikasi Telegram, log audit, login admin + verifikasi dua langkah.

Pemasangan: [docs/PASANG.md](docs/PASANG.md). Laravel 13, PHP 8.3+, MariaDB, rsync.
