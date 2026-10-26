<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected string $tmp = '';

    /** Folder sementara: data Backup Master + "VM sumber" palsu. */
    protected function siapkanFolder(): string
    {
        $this->tmp = sys_get_temp_dir().'/bmtes-'.bin2hex(random_bytes(5));
        File::ensureDirectoryExists($this->tmp.'/data');
        config(['bm.data_dir' => $this->tmp.'/data', 'bm.uji_otomatis' => true, 'bm.telegram_token' => '', 'cache.default' => 'array']);

        return $this->tmp;
    }

    protected function tearDown(): void
    {
        if ($this->tmp !== '' && str_contains($this->tmp, 'bmtes-')) {
            File::deleteDirectory($this->tmp);
        }
        parent::tearDown();
    }

    /** Buat "backup" SQLite seperti Absensi (absensi_*.sqlite3). */
    protected function sqliteBackup(string $path, array $siswa = ['Budi', 'Ani'], bool $bk = true): void
    {
        File::ensureDirectoryExists(dirname($path));
        $p = new \PDO('sqlite:'.$path);
        $p->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, username TEXT, password TEXT, remember_token TEXT)');
        $p->exec("INSERT INTO users (username, password, remember_token) VALUES ('admin', '\$2y\$10\$RAHASIAHASH', 'tok123')");
        $p->exec('CREATE TABLE students (id INTEGER PRIMARY KEY, nama TEXT, nis TEXT)');
        foreach ($siswa as $i => $n) {
            $p->exec("INSERT INTO students (nama, nis) VALUES ('$n', '".(1001 + $i)."')");
        }
        if ($bk) {
            $p->exec('CREATE TABLE bk_records (id INTEGER PRIMARY KEY, catatan TEXT)');
            $p->exec("INSERT INTO bk_records (catatan) VALUES ('catatan konseling rahasia')");
        }
        $p = null;
    }
}
