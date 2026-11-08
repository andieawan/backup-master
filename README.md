# Master App (Backup Master) — SMKS Islam Bustanul Ulum Pakusari

Satu tempat untuk **semua backup aplikasi sekolah** (Pusat, Website, Jurnal Guru, BK, TU, PKL, Tanggungan Siswa):

- **Menarik** backup harian dari setiap VM lewat rsync/SSH, dengan akses baca-saja (sumber tidak bisa menulis atau menghapus di sini).
- **Memeriksa**: keutuhan berkas (gzip/sha256), umur backup, **uji pulih otomatis** ke database terpisah, perbandingan jumlah baris.
- **Menyajikan jadi satu**: dasbor status, riwayat, unduh, **Jelajah Data** (isi database semua aplikasi, baca saja), isi arsip & potret folder (foto, scan surat).
- **Master App** (khusus admin, baca saja): **Cari & Profil** — satu kotak cari ke salinan database semua aplikasi, dan profil
  satu siswa/guru yang menggabungkan data induk Pusat dengan absensi & nilai Jurnal, catatan BK (tetap wajib alasan), mutasi
  & surat TU, PKL, dan tagihan Tanggungan (dikaitkan lewat UUID Pusat; atur di `config/bm.php` → `cari`, `profil`).
  Setiap tabel di Jelajah Data bisa **diunduh sebagai Excel** (kolom password/token tidak ikut). Data diperbarui tiap malam
  setelah backup ditarik & lolos uji pulih, atau kapan saja lewat *Tarik sekarang*.
- **Bertingkat di beberapa tempat**: Utama (VM Proxmox di HDD-BACKUP) → salinan di komputer sekolah → salinan di VPS. Aplikasinya sama.
- Retensi 14 harian / 8 mingguan / 12 bulanan, notifikasi Telegram, log audit, login admin + verifikasi dua langkah.

Pemasangan: [docs/PASANG.md](docs/PASANG.md). Laravel 13, PHP 8.3+, MariaDB, rsync.
