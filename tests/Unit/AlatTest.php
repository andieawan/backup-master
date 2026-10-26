<?php

namespace Tests\Unit;

use App\Services\Backup\Katalog;
use App\Services\Backup\Retensi;
use App\Support\Totp;
use Tests\TestCase;

class AlatTest extends TestCase
{
    public function test_totp_sesuai_rfc6238(): void
    {
        $r = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ'; // "12345678901234567890"
        $this->assertSame('287082', Totp::kode($r, 59));
        $this->assertSame('081804', Totp::kode($r, 1111111109));
        $this->assertTrue(Totp::cocok($r, '081804', 1111111109 + 25));
        $this->assertFalse(Totp::cocok($r, '081804', 1111111109 + 95));
        $this->assertSame(32, strlen(Totp::rahasiaBaru()));
    }

    public function test_jenis_dan_waktu_dari_nama_berkas(): void
    {
        $this->assertSame('db', Katalog::jenis('db_2026-10-26_0130.sql.gz'));
        $this->assertSame('db', Katalog::jenis('absensi_2026-10-26T02-00-00.sql'));
        $this->assertSame('arsip', Katalog::jenis('berkas_2026-10-26_0130.tar.gz'));
        $this->assertNull(Katalog::jenis('catatan.txt'));
        // WIB 01:30 = 18:30 UTC hari sebelumnya
        $this->assertSame('2026-10-25 18:30:00', Katalog::waktu('pusat_2026-10-26_0130.sql.gz')->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-25 19:00:00', Katalog::waktu('absensi_2026-10-26T02-00-00.sql')->format('Y-m-d H:i:s'));
        $this->assertSame(1700000000, Katalog::waktu('tanpa-tanggal.sql', 1700000000)->getTimestamp());
    }

    public function test_retensi_harian_mingguan_bulanan(): void
    {
        $hari = [];
        for ($i = 0; $i < 400; $i++) {
            $hari[] = date('Y-m-d', strtotime("2026-10-26 -$i days"));
        }
        $simpan = Retensi::pilih($hari, 14, 8, 12);
        $this->assertContains('2026-10-26', $simpan);
        $this->assertContains('2026-10-13', $simpan);   // hari ke-14
        $this->assertNotContains('2026-10-12', $simpan); // bukan akhir minggu/bulan
        $this->assertContains('2026-09-30', $simpan);   // akhir bulan
        $this->assertContains('2025-11-30', $simpan);   // bulan ke-12
        $this->assertNotContains('2025-10-31', $simpan); // bulan ke-13
        $this->assertLessThanOrEqual(14 + 8 + 12, count($simpan));
        $this->assertSame(['2026-01-01'], Retensi::pilih(['2026-01-01'], 0, 0, 0)); // terbaru selalu disimpan
    }
}
