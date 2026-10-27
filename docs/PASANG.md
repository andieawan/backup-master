# Memasang Backup Master

## Gambaran

```
 VM Pusat ─┐
 VM Absensi┤                       ┌──────────────────────────┐        ┌───────────────────────┐
 VM BK     ├── tarik tiap malam ──▶│ UTAMA  (VM di Proxmox,   │◀─ tarik│ KOMPUTER SEKOLAH      │
 VM TU     │   (rsync, baca saja)  │ disk di HDD-BACKUP)      │        │ (salinan, mengejar    │
 VM PKL    │                       └──────────────────────────┘        │  saat dinyalakan)     │
 VM Website┘                                   ▲                       └───────────────────────┘
                                               │ tarik lewat WireGuard (MikroTik)
                                       ┌───────┴───────┐
                                       │ VPS (salinan) │  ← bila anggaran disetujui
                                       └───────────────┘
```

- **Selalu menarik.** Backup Master yang menghubungi sumber. Kunci SSH-nya di sumber dipaksa menjalankan `rrsync -ro`
  (baca saja, satu folder). VM aplikasi yang diretas tidak bisa menghapus backup di sini.
- **Berkas lama tidak ditimpa.** Kalau berkas di sumber berubah (misalnya terenkripsi ransomware), salinan di sini tetap.
- **Database aplikasi tidak pernah disentuh.** Uji pulih memuat dump ke database `bmj_<kode>` di mesin Backup Master sendiri,
  memakai user DB terpisah (`backup_master_uji`) yang hanya berhak atas `bmj_*`. Dump dari VM yang diretas tidak bisa
  mengubah akun atau katalog Backup Master.
- **Jangan dibuka ke internet.** Nginx hanya menerima jaringan lokal. Dari luar, gunakan VPN.

> Backup Master melengkapi, tidak menggantikan, backup VM bawaan Proxmox (vzdump). Aktifkan juga vzdump mingguan
> semua VM ke HDD-BACKUP (Datacenter → Backup), agar bisa memulihkan satu VM utuh.

## 1. Utama: VM di Proxmox (disk di HDD-BACKUP)

1. Buat VM Ubuntu Server 24.04 dengan disk sistem 20 GB di SSD dan **disk kedua di storage HDD-BACKUP** (mis. 600 GB). Mount disk kedua di `/srv/backup-master`:
   ```bash
   sudo mkfs.ext4 /dev/sdb && sudo mkdir -p /srv/backup-master
   echo '/dev/sdb /srv/backup-master ext4 defaults 0 2' | sudo tee -a /etc/fstab && sudo mount -a
   ```
2. Pasang:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/andieawan/backup-master/main/deploy/install.sh -o install.sh
   sudo LOKASI="Utama (Proxmox)" bash install.sh
   ```
   Catat password admin yang tampil. Buka `http://IP-VM/`. Pada login pertama Anda diminta mengatur verifikasi dua langkah
   (Google Authenticator / Microsoft Authenticator / Aegis).
3. **Sumber → Tambah sumber → klik templat** (Pusat, Absensi, BK, TU, PKL, Website), isi **Host** = IP VM-nya, lalu Tambah.
4. Halaman sumber menampilkan perintah `siapkan-sumber.sh`. **Jalankan perintah itu di VM aplikasinya**, misalnya:
   ```bash
   curl -fsSL https://raw.githubusercontent.com/andieawan/backup-master/main/deploy/siapkan-sumber.sh -o siapkan-sumber.sh
   sudo bash siapkan-sumber.sh --user bmtarik --folder /var/backups/tu-pusat --kunci 'ssh-ed25519 AAAA... backup-master@utama' --dari IP-UTAMA
   ```
   (`--dari` membatasi agar hanya IP Backup Master yang bisa memakai kunci itu. Disarankan.)
5. Klik **Tarik sekarang**. Dalam ±1 menit status berubah menjadi OK, lalu uji pulih berjalan otomatis.

Folder bawaan per aplikasi (sudah terisi di templat):

| Aplikasi | Folder di VM | Isi |
|---|---|---|
| Pusat | `/var/backups/pusat` | `pusat_*.sql.gz` (01:15) |
| Absensi | `/var/www/absensi/storage/app` | `backups/absensi_*.sql` (02:00) + potret `private/` (scan surat BK/TU lama) |
| BK | `/var/backups/bk-pusat` | `db_*`, `berkas_*` |
| TU | `/var/backups/tu-pusat` | `db_*`, `berkas_*` (01:30) |
| PKL | `/var/backups/pkl-pusat` | `db_*`, `berkas_*` (01:45, termasuk foto absen) |
| Website | `/var/backups/smkibu` | `db_*`, `foto_*`, `images_*` |

Tarik bawaan pukul 02:30 (Absensi: atur 02:45 karena backupnya 02:00).

## 2. Salinan: komputer sekolah

Bisa langsung di komputer Linux, atau di VM (VirtualBox / Hyper-V) dengan Ubuntu Server bila komputernya Windows.
Pilih komputer yang paling sering menyala. Jadwal mengejar sendiri: bila semalam mati, penarikan berjalan saat komputer dinyalakan.

```bash
sudo LOKASI="Komputer TU" bash install.sh
```
Lalu di salinan: **Sumber → templat "Backup Master utama"**, isi Host = IP Utama, Folder `/srv/backup-master`.
Jalankan perintah `siapkan-sumber.sh` yang tampil **di VM Utama**, lalu Tarik sekarang.
Salinan mengambil semua aplikasi sekaligus, mencocokkan sha256 dengan MANIFEST Utama, lalu menguji pulih sendiri.

## 3. Salinan: VPS (nanti)

VPS menarik dari Utama tanpa membuka port di sekolah: **WireGuard di MikroTik** (RouterOS 7).
1. MikroTik: `/interface wireguard add name=wg-vps listen-port=13231`, beri IP `10.99.0.1/30`, tambahkan peer VPS
   (allowed-address `10.99.0.2/32`), dan izinkan firewall UDP 13231 dari IP VPS saja. Route `192.168.10.0/24` sudah lokal.
2. VPS: `apt install wireguard`, `wg0` dengan IP `10.99.0.2/30`, endpoint `45.158.10.5:13231`, AllowedIPs `192.168.10.0/24`, `PersistentKeepalive=25`.
3. Pasang Backup Master di VPS: `sudo LOKASI="VPS" IZINKAN="10.99.0.0/30" bash install.sh`. Halaman web VPS hanya terbuka lewat VPN.
4. Isi `BM_RSYNC_BWLIMIT=2000` (KB/s) di `.env` VPS agar internet sekolah tidak penuh. Sumber: Utama, Host = IP LAN Utama.
   Di Utama: `siapkan-sumber.sh ... --dari 10.99.0.2`.

Alternatif tanpa VPN: port-forward SSH Utama di MikroTik khusus dari IP VPS (dst-port acak, src-address = IP VPS).

## Notifikasi Telegram
Buat bot lewat @BotFather, kirim pesan ke bot, ambil chat id (`https://api.telegram.org/bot<TOKEN>/getUpdates`). Isi di `.env`:
```
BM_TELEGRAM_TOKEN=123456:ABC...
BM_TELEGRAM_CHAT=123456789
```
lalu `sudo -u www-data php artisan config:cache`. Yang dikirim: penarikan gagal, berkas rusak, uji pulih gagal atau turun drastis,
dan ringkasan setiap pukul 07:00.

## Memulihkan aplikasi
Buka berkas → **Langkah memulihkan ke aplikasi**. Perintahnya sudah terisi (salin ke VM, matikan sementara aplikasi, impor, migrate, hidupkan).
Pemulihan sengaja dijalankan manual agar tidak ada salah tekan yang menimpa data.

## Jelajah Data & privasi
- Data yang tampil berasal dari **backup terakhir yang lolos uji pulih**, baca saja.
- Kolom password, token, dan secret **selalu disembunyikan**.
- Tabel rahasia (bawaan: `bk_catatan`, `bk_records`) hanya terbuka setelah admin menulis alasan. Alasannya tercatat di **Log**.
- Semua login, unduhan, dan pembukaan data tercatat di Log.

## Perintah berguna
```bash
cd /var/www/backup-master
sudo -u www-data php artisan bm:periksa          # kesiapan
sudo -u www-data php artisan bm:tarik [kode]     # tarik sekarang
sudo -u www-data php artisan bm:uji [kode]       # uji pulih
sudo -u www-data php artisan bm:ringkasan --kirim
sudo -u www-data php artisan bm:buat-admin nama  # admin baru / ganti password
sudo -u www-data php artisan bm:buat-admin nama --reset-2fa   # HP hilang
sudo bash deploy/update.sh                       # perbarui aplikasi
```
