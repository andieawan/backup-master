#!/usr/bin/env bash
# Dijalankan di SUMBER (VM aplikasi, atau Backup Master utama) agar Backup Master bisa MENARIK backup — baca saja.
#
#   sudo bash siapkan-sumber.sh --folder /var/backups/tu-pusat --kunci 'ssh-ed25519 AAAA... backup-master@utama'
#
# Opsi:
#   --folder DIR   folder yang dibuka (hanya itu, dan hanya untuk dibaca)
#   --kunci KEY    kunci publik Backup Master (tampil di menu Sumber)
#   --user NAMA    user khusus (default bmtarik)
#   --dari IP      hanya terima dari IP ini (disarankan; boleh beberapa dipisah koma)
#
# Yang dilakukan: membuat user tanpa password; kuncinya dipaksa menjalankan "rrsync -ro" pada folder itu
# (tidak bisa login shell, tidak bisa menulis/menghapus, tidak bisa keluar dari folder). rrsync dijalankan lewat sudo
# agar bisa membaca berkas milik root/www-data. Ulangi skrip ini dengan kunci lain untuk menambah Backup Master.
set -euo pipefail
FOLDER=""; KUNCI=""; USERNYA="bmtarik"; DARI=""
while [ $# -gt 0 ]; do
    case "$1" in
        --folder) FOLDER="${2%/}"; shift 2 ;;
        --kunci) KUNCI="$2"; shift 2 ;;
        --user) USERNYA="$2"; shift 2 ;;
        --dari) DARI="$2"; shift 2 ;;
        *) echo "Opsi tidak dikenal: $1" >&2; exit 1 ;;
    esac
done
gagal() { printf '\033[1;31mGAGAL: %s\033[0m\n' "$*" >&2; exit 1; }
[ "$(id -u)" -eq 0 ] || gagal "Jalankan dengan sudo."
[ -n "$FOLDER" ] && [ -d "$FOLDER" ] || gagal "Folder '$FOLDER' tidak ada. Jalankan dulu backup aplikasinya (sudo bash deploy/backup.sh)."
case "$FOLDER" in /|/etc|/root|/home|/var|/usr) gagal "Folder terlalu luas: $FOLDER" ;; esac
[[ "$KUNCI" =~ ^ssh-(ed25519|rsa)\ [A-Za-z0-9+/=]+ ]] || gagal "Kunci tidak valid (salin utuh dari menu Sumber)."
[[ "$USERNYA" =~ ^[a-z_][a-z0-9_-]{0,31}$ ]] || gagal "Nama user tidak valid."
[ -z "$DARI" ] || [[ "$DARI" =~ ^[0-9a-fA-F.:/,]+$ ]] || gagal "--dari tidak valid."

command -v rsync >/dev/null || apt-get install -y rsync
command -v sudo >/dev/null || apt-get install -y sudo
RRSYNC="$(command -v rrsync || true)"
if [ -z "$RRSYNC" ]; then
    for f in /usr/share/doc/rsync/scripts/rrsync /usr/share/doc/rsync/scripts/rrsync.gz; do
        if [ -f "$f" ]; then
            case "$f" in *.gz) gunzip -c "$f" > /usr/local/bin/rrsync ;; *) cp "$f" /usr/local/bin/rrsync ;; esac
            chmod 755 /usr/local/bin/rrsync; RRSYNC=/usr/local/bin/rrsync; break
        fi
    done
fi
[ -n "$RRSYNC" ] || gagal "rrsync tidak ditemukan (butuh paket rsync 3.2+)."
MUNGE=""; "$RRSYNC" -help 2>&1 | grep -q -- '-munge' && MUNGE=" -munge"
PERINTAH="$RRSYNC -ro$MUNGE $FOLDER"

if ! id "$USERNYA" >/dev/null 2>&1; then
    useradd --system --create-home --home-dir "/var/lib/$USERNYA" --shell /bin/sh "$USERNYA"
fi
passwd -l "$USERNYA" >/dev/null 2>&1 || true
H="$(getent passwd "$USERNYA" | cut -d: -f6)"
install -d -m 700 -o "$USERNYA" -g "$USERNYA" "$H/.ssh"
AK="$H/.ssh/authorized_keys"; touch "$AK"
INTI="$(echo "$KUNCI" | awk '{print $2}')"
OPSI="command=\"sudo -n $PERINTAH\",restrict"
[ -n "$DARI" ] && OPSI="$OPSI,from=\"$DARI\""
grep -v -- "$INTI" "$AK" > "$AK.baru" || true
echo "$OPSI $KUNCI" >> "$AK.baru"
mv "$AK.baru" "$AK"; chown "$USERNYA:$USERNYA" "$AK"; chmod 600 "$AK"

SUDO="/etc/sudoers.d/backup-master-$USERNYA"
{
    echo "# Dibuat siapkan-sumber.sh: $USERNYA hanya boleh menjalankan rrsync baca-saja pada folder di bawah."
    echo "Defaults:$USERNYA !requiretty"
    echo "Defaults:$USERNYA env_keep += \"SSH_ORIGINAL_COMMAND\""
    grep -h "^$USERNYA ALL" "$SUDO" 2>/dev/null | grep -v -F "$PERINTAH" || true
    echo "$USERNYA ALL=(root) NOPASSWD: $PERINTAH"
} > "$SUDO.baru"
visudo -cf "$SUDO.baru" >/dev/null || { rm -f "$SUDO.baru"; gagal "Aturan sudo tidak valid."; }
mv "$SUDO.baru" "$SUDO"; chmod 440 "$SUDO"

# sshd harus mengizinkan login kunci untuk user ini (AllowUsers yang ketat perlu ditambah manual).
if grep -Eqs '^\s*AllowUsers' /etc/ssh/sshd_config /etc/ssh/sshd_config.d/*.conf 2>/dev/null; then
    echo "PERHATIAN: sshd memakai AllowUsers — tambahkan '$USERNYA' lalu: sudo systemctl reload ssh"
fi
command -v sshd >/dev/null || { apt-get install -y openssh-server; systemctl enable --now ssh; }

printf '\033[1;32mSiap.\033[0m Backup Master dapat menarik (baca saja) %s sebagai %s@%s\n' "$FOLDER" "$USERNYA" "$(hostname -I | awk '{print $1}')"
echo "Isi di Backup Master → Sumber: Host = $(hostname -I | awk '{print $1}'), User = $USERNYA. Lalu klik Tarik sekarang."
