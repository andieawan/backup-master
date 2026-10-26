<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\Sumber;
use App\Models\Tarikan;
use App\Services\Audit;
use App\Support\Dates;
use App\Support\Jalankan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;

/**
 * Menarik backup dengan rsync. Prinsip keamanan:
 *  - SELALU menarik (Backup Master yang menghubungi sumber), sumber tidak punya akses tulis ke sini.
 *  - Di sumber, kunci SSH Backup Master dibatasi rrsync baca-saja ke satu folder (deploy/siapkan-sumber.sh).
 *  - Berkas yang sudah ada TIDAK ditimpa (--ignore-existing): bila sumber terkena ransomware, salinan lama tetap utuh.
 */
class Penarik
{
    /** Sumber yang waktunya ditarik sekarang. */
    public static function jatuhTempo(?CarbonImmutable $kini = null): \Illuminate\Support\Collection
    {
        $kini ??= CarbonImmutable::now(Dates::tz());

        return Sumber::where('aktif', true)->get()->filter(function (Sumber $s) use ($kini) {
            if ($s->perlu_tarik) {
                return true;
            }
            [$h, $m] = array_map('intval', explode(':', $s->jam ?: '02:30') + [1 => 0]);
            $jadwal = $kini->setTime($h, $m);
            if ($kini < $jadwal) {
                $jadwal = $jadwal->subDay(); // belum jam tarik hari ini: yang dihitung jadwal kemarin
            }
            $berhasil = $s->terakhir_berhasil?->toImmutable()->setTimezone(Dates::tz());
            if ($berhasil && $berhasil >= $jadwal) {
                return false; // sudah berhasil sejak jadwal terakhir
            }
            // gagal: coba lagi tiap 60 menit (komputer yang baru menyala langsung mengejar)
            $mulai = $s->terakhir_mulai?->toImmutable();

            return ! $mulai || $s->terakhir_status !== 'gagal' || $mulai->diffInMinutes($kini->utc(), true) >= 60;
        })->values();
    }

    private static function ssh(Sumber $s): string
    {
        $o = ['ssh', '-i', config('bm.ssh_key'), '-p', (string) $s->port, '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes',
            '-o', 'StrictHostKeyChecking=accept-new', '-o', 'UserKnownHostsFile='.config('bm.ssh_known_hosts'),
            '-o', 'ConnectTimeout=20', '-o', 'ServerAliveInterval=30'];

        return implode(' ', array_map('escapeshellarg', $o));
    }

    /** Alamat sumber rsync untuk subfolder relatif (SSH: relatif terhadap akar rrsync; lokal: path penuh). */
    private static function src(Sumber $s, string $sub): string
    {
        $sub = trim($sub, '/');
        if ($s->lokal()) {
            return rtrim($s->folder, '/').'/'.($sub === '' || $sub === '.' ? '' : $sub.'/');
        }

        return "{$s->pengguna}@{$s->host}:".($sub === '' || $sub === '.' ? './' : $sub.'/');
    }

    private static function rsync(Sumber $s, array $opsi, string $src, string $dst): array
    {
        $cmd = ['rsync', '--timeout=300', '--out-format=>%l %n', ...$opsi];
        if ((int) config('bm.rsync_bwlimit') > 0) {
            $cmd[] = '--bwlimit='.(int) config('bm.rsync_bwlimit');
        }
        if (! $s->lokal()) {
            $cmd[] = '-e';
            $cmd[] = self::ssh($s);
        }
        [$k, $out, $err] = Jalankan::perintah([...$cmd, $src, $dst], (int) config('bm.rsync_timeout', 1800));
        if ($k !== 0 && $k !== 24) { // 24 = sebagian berkas hilang selama transfer (sumber sedang membersihkan) — wajar
            throw new \RuntimeException(self::pesanRsync($k, $err));
        }
        $n = $bait = 0;
        foreach (explode("\n", $out) as $baris) {
            if (preg_match('/^>(\d+) (.+[^\/])$/', $baris, $m)) {
                $n++;
                $bait += (int) $m[1];
            }
        }

        return [$n, $bait];
    }

    private static function pesanRsync(int $k, string $err): string
    {
        $e = Jalankan::ringkas($err, 400);
        $saran = match (true) {
            str_contains($err, 'Permission denied (publickey') => 'Kunci SSH Backup Master belum dipasang di sumber (jalankan deploy/siapkan-sumber.sh di sumber).',
            str_contains($err, 'Connection refused'), str_contains($err, 'timed out'), str_contains($err, 'No route') => 'Sumber tidak terjangkau: VM mati, IP salah, atau firewall.',
            str_contains($err, 'Host key verification failed'), str_contains($err, 'REMOTE HOST IDENTIFICATION') => 'Kunci host sumber berubah (VM dipasang ulang?). Hapus barisnya di '.config('bm.ssh_known_hosts').' bila memang sengaja.',
            str_contains($err, 'No such file'), str_contains($err, 'change_dir') => 'Folder di sumber tidak ada. Periksa isian Subfolder arsip/cermin.',
            str_contains($err, 'No space left') => 'Disk Backup Master penuh.',
            default => null,
        };

        return "rsync gagal (kode $k)".($saran ? ": $saran" : '').($e !== '' ? " — $e" : '');
    }

    /** Berkas pengecualian rsync dari daftar terhapus (relatif terhadap akar transfer). */
    private static function exclude(string $awalan): string
    {
        $f = Katalog::dir('.lokal/exclude-'.md5($awalan).'.txt');
        $baris = [];
        foreach (Katalog::terhapus() as $p) {
            if ($awalan === '') {
                $baris[] = '/'.$p.(str_contains($p, '/cermin/') ? '/' : '');
            } elseif (str_starts_with($p, $awalan)) {
                $baris[] = '/'.substr($p, strlen($awalan));
            }
        }
        file_put_contents($f, implode("\n", $baris)."\n");

        return $f;
    }

    public static function tarik(Sumber $s): Tarikan
    {
        Katalog::siapkan();
        $t = Tarikan::create(['sumber_id' => $s->id, 'mulai' => now('UTC'), 'status' => 'jalan']);
        $s->update(['terakhir_mulai' => now('UTC'), 'perlu_tarik' => false]);
        try {
            [$n, $bait, $catatan] = $s->jenis === 'master' ? self::dariMaster($s) : self::dariAplikasi($s);
            $baru = Katalog::pindai();
            if ($s->jenis === 'master') {
                $catatan = trim($catatan.' '.self::cocokkanManifest($s));
                $baru = $baru->map(fn (Berkas $b) => $b->fresh())->filter()->values();
            }
            Retensi::jalankan();
            Katalog::tulisManifest();
            $rusak = $baru->where('utuh', false);
            // Berkas rusak yang masih "muda" (< 1 jam) kemungkinan tertarik saat sumber masih menulisnya: hapus agar ditarik ulang.
            // Yang lebih tua memang rusak di sumber: disimpan dengan tanda Rusak (tidak dipakai uji pulih) dan dikabarkan sekali.
            $ulang = $rusak->filter(fn (Berkas $b) => is_file($b->full()) && time() - filemtime($b->full()) < 3600);
            $ulang->each(function (Berkas $b) {
                @unlink($b->full());
                $b->delete();
            });
            $pesan = trim($catatan.($rusak->isNotEmpty() ? ' Berkas tidak utuh: '.$rusak->map(fn ($b) => $b->nama.' ('.$b->pesan.')')->implode('; ') : ''));
            $t->update(['selesai' => now('UTC'), 'status' => $ulang->isNotEmpty() ? 'gagal' : 'ok', 'berkas_baru' => $n, 'bait' => $bait, 'pesan' => $pesan ?: null]);
            $s->update(['terakhir_status' => $t->status, 'terakhir_pesan' => $t->pesan] + ($t->status === 'ok' ? ['terakhir_berhasil' => now('UTC')] : []));
            if ($rusak->isNotEmpty()) {
                Notifikasi::kirim("⚠️ {$s->nama}: berkas backup tidak utuh — ".$rusak->pluck('nama')->implode(', '));
            }
            if (config('bm.uji_otomatis')) {
                foreach ($baru->where('jenis', 'db')->where('utuh', true)->pluck('aplikasi')->unique() as $kode) {
                    Pemulih::ujiTerbaru($kode);
                }
            }
        } catch (\Throwable $e) {
            $t->update(['selesai' => now('UTC'), 'status' => 'gagal', 'pesan' => Jalankan::ringkas($e->getMessage(), 1000)]);
            $s->update(['terakhir_status' => 'gagal', 'terakhir_pesan' => $t->pesan]);
            Notifikasi::kirim("❌ Tarik backup {$s->nama} gagal di ".config('bm.lokasi').": ".$t->pesan);
        }
        Audit::catat('Tarik backup', auth()->user()?->username ?? 'sistem', "{$s->nama}: {$t->status}, {$t->berkas_baru} berkas baru");

        return $t;
    }

    /** @return array{0:int,1:int,2:string} */
    private static function dariAplikasi(Sumber $s): array
    {
        if (! Katalog::kodeValid($s->kode)) {
            throw new \RuntimeException('Kode sumber tidak valid.');
        }
        Aplikasi::pastikan($s->kode);
        $n = $bait = 0;
        if ($s->arsip !== null && $s->arsip !== '') {
            $dst = Katalog::dir($s->kode.'/arsip');
            File::ensureDirectoryExists($dst, 0750);
            $opsi = ['-rt', '--ignore-existing', '--no-perms', '--chmod=F640,D750',
                '--exclude-from='.self::exclude($s->kode.'/arsip/'), '--exclude=*/', '--exclude=.*'];
            foreach (['*.sql', '*.sql.gz', '*.sqlite3', '*.sqlite', '*.tar.gz', '*.tgz', '*.tar', '*.zip'] as $p) {
                $opsi[] = '--include='.$p;
            }
            $opsi[] = '--exclude=*';
            [$a, $b] = self::rsync($s, $opsi, self::src($s, $s->arsip), $dst.'/');
            $n += $a;
            $bait += $b;
        }
        if ($s->cermin !== null && $s->cermin !== '') {
            [$a, $b] = self::potret($s);
            $n += $a;
            $bait += $b;
        }

        return [$n, $bait, ''];
    }

    /** Potret harian folder (rsync --link-dest: berkas yang tidak berubah berbagi ruang disk dengan potret kemarin). */
    private static function potret(Sumber $s): array
    {
        $base = Katalog::dir($s->kode.'/cermin');
        File::ensureDirectoryExists($base, 0750);
        $hari = Dates::today();
        $sebelum = collect(glob("$base/*", GLOB_ONLYDIR) ?: [])->map('basename')
            ->filter(fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d < $hari)->sort()->last();
        $tujuan = "$base/$hari";
        $tmp = is_dir($tujuan) ? $tujuan : "$base/$hari.tmp";
        File::ensureDirectoryExists($tmp, 0750);
        $opsi = ['-rlt', '--delete', '--no-perms', '--chmod=F640,D750', '--exclude=backups/'];
        if ($sebelum) {
            $opsi[] = '--link-dest='."$base/$sebelum";
        }
        $hasil = self::rsync($s, $opsi, self::src($s, $s->cermin), $tmp.'/');
        if ($tmp !== $tujuan) {
            rename($tmp, $tujuan);
        }

        return $hasil;
    }

    /** Menyalin seluruh isi Backup Master lain (utama → salinan), lalu mencocokkan sha256 dengan MANIFEST-nya. */
    private static function dariMaster(Sumber $s): array
    {
        $dst = Katalog::dir().'/';
        $opsi = ['-rltH', '--ignore-existing', '--no-perms', '--chmod=F640,D750', '--exclude-from='.self::exclude(''),
            '--exclude=/MANIFEST.json', '--exclude=/MANIFEST.json.tmp', '--exclude=/.lokal/', '--exclude=*.tmp/', '--exclude=.*'];
        [$n, $bait] = self::rsync($s, $opsi, self::src($s, '.'), $dst);
        // info.json boleh berubah (nama/tabel rahasia diubah di utama): selalu perbarui
        self::rsync($s, ['-rtm', '--no-perms', '--chmod=F640,D750', '--exclude=/.lokal/', '--include=/*/', '--include=/*/info.json', '--exclude=*'], self::src($s, '.'), $dst);

        $man = Katalog::dir('.lokal/manifest-'.$s->kode.'.json');
        @unlink($man);
        $src = $s->lokal() ? rtrim($s->folder, '/').'/MANIFEST.json' : "{$s->pengguna}@{$s->host}:MANIFEST.json";
        self::rsync($s, ['-t'], $src, $man);

        return [$n, $bait, ''];
    }

    /** Cocokkan sha256 berkas di sini dengan MANIFEST sumber. @return string catatan */
    private static function cocokkanManifest(Sumber $s): string
    {
        $man = Katalog::dir('.lokal/manifest-'.$s->kode.'.json');
        $isi = is_file($man) ? json_decode((string) file_get_contents($man), true) : null;
        if (! is_array($isi) || ! isset($isi['berkas'])) {
            return 'MANIFEST sumber tidak terbaca; verifikasi sha256 dilewati.';
        }
        $lokal = Berkas::whereNotNull('sha256')->pluck('sha256', 'path');
        $terhapus = array_flip(Katalog::terhapus());
        $beda = [];
        $kurang = 0;
        foreach ($isi['berkas'] as $m) {
            $path = (string) ($m['path'] ?? '');
            if (! isset($lokal[$path])) {
                $kurang += isset($terhapus[$path]) ? 0 : 1;
            } elseif ($lokal[$path] !== ($m['sha256'] ?? null)) {
                $beda[] = basename($path);
                Berkas::where('path', $path)->update(['utuh' => false, 'pesan' => 'sha256 berbeda dengan sumber '.$s->nama]);
            }
        }

        return ($beda ? 'Berbeda dengan sumber: '.implode(', ', $beda).'. ' : '').($kurang ? "$kurang berkas di sumber belum ada di sini." : '');
    }
}
