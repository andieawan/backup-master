<?php

namespace App\Services\Backup;

use App\Models\Aplikasi;
use App\Models\Berkas;
use App\Models\Sumber;

/** Menyusun langkah pemulihan ke VM aplikasi. Backup Master tidak menimpa database aplikasi sendiri (sengaja). */
class Panduan
{
    /** @return array<int, array{judul:string, di:string, perintah:string}> */
    public static function langkah(Berkas $b): array
    {
        $a = Aplikasi::where('kode', $b->aplikasi)->first();
        $s = Sumber::where('kode', $b->aplikasi)->where('jenis', 'aplikasi')->first();
        $host = $s && ! $s->lokal() ? $s->host : '<IP-VM-'.$b->aplikasi.'>';
        $dir = $a?->app_dir ?: '/var/www/<aplikasi>';
        $db = $a?->db ?: '<nama_database>';
        $q = fn ($x) => escapeshellarg($x);
        $tmp = '/tmp/'.$b->nama;
        $langkah = [];
        if ($b->jenis === 'cermin') {
            $langkah[] = ['judul' => 'Salin folder potret ke VM', 'di' => 'Backup Master ini',
                'perintah' => 'sudo rsync -a '.$q($b->full().'/').' <admin>@'.$host.':/tmp/pulih-'.$b->nama.'/'];
            $langkah[] = ['judul' => 'Kembalikan ke folder aplikasi (berkas yang ada tertimpa)', 'di' => "VM $host",
                'perintah' => "sudo rsync -a /tmp/pulih-{$b->nama}/ $dir/storage/app/private/\nsudo chown -R www-data:www-data $dir/storage/app"];

            return $langkah;
        }
        $langkah[] = ['judul' => 'Salin berkas backup ke VM tujuan', 'di' => 'Backup Master ini', 'perintah' => 'sudo scp '.$q($b->full()).' <admin>@'.$host.':/tmp/'];
        if ($b->jenis === 'db') {
            $impor = str_ends_with(strtolower($b->nama), '.gz') ? "gunzip -c $tmp | sudo mysql $db" : "sudo mysql $db < $tmp";
            $langkah[] = ['judul' => 'Backup kondisi sekarang dulu (jaga-jaga), lalu matikan sementara aplikasinya', 'di' => "VM $host",
                'perintah' => "cd $dir\nsudo bash deploy/backup.sh 2>/dev/null || true\nsudo -u www-data php artisan down"];
            $langkah[] = ['judul' => 'Kosongkan database lalu impor backup', 'di' => "VM $host",
                'perintah' => "sudo mysql -e \"DROP DATABASE \\`$db\\`; CREATE DATABASE \\`$db\\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci\"\n$impor"];
            $langkah[] = ['judul' => 'Selaraskan struktur & hidupkan lagi', 'di' => "VM $host",
                'perintah' => "sudo -u www-data php artisan migrate --force\nsudo -u www-data php artisan optimize:clear\nsudo -u www-data php artisan up\nrm $tmp"];
        } elseif (str_starts_with($b->nama, 'wol_')) {
            // WOL Panel: isi arsip = isi /opt/wol-panel (config.json, vm.json, scan.json, wallpapers/, ssh/)
            $langkah[] = ['judul' => 'Hentikan panel, bongkar arsip, lalu hidupkan lagi', 'di' => "Container $host",
                'perintah' => "sudo systemctl stop wol-panel\nsudo tar -xzf $tmp -C $dir\nsudo chown -R wolpanel:wolpanel $dir && sudo chmod 600 $dir/config.json\nsudo systemctl start wol-panel\nrm $tmp"];
        } else {
            $ke = match (true) {
                str_starts_with($b->nama, 'foto_') => "$dir/storage/app/public",
                str_starts_with($b->nama, 'images_') => "$dir/public",
                default => "$dir/storage/app",
            };
            $buka = str_ends_with(strtolower($b->nama), '.zip') ? "sudo unzip -o $tmp -d $ke" : "sudo tar -xzf $tmp -C $ke";
            $langkah[] = ['judul' => 'Bongkar arsip ke folder aplikasi (berkas yang ada tertimpa)', 'di' => "VM $host",
                'perintah' => "$buka\nsudo chown -R www-data:www-data $dir/storage\nrm $tmp"];
        }

        return $langkah;
    }
}
