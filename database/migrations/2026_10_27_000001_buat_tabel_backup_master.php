<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backup Master: akun admin, sumber penarikan, katalog berkas backup, riwayat tarik, uji pulih, dan log audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('username')->unique();
            $t->string('nama');
            $t->json('roles')->nullable();
            $t->string('password')->nullable();
            $t->text('totp_secret')->nullable();          // terenkripsi (APP_KEY)
            $t->timestamp('totp_aktif_pada')->nullable();
            $t->boolean('aktif')->default(true);
            $t->rememberToken();
            $t->timestamps();
        });

        Schema::create('sessions', function (Blueprint $t) {
            $t->string('id')->primary();
            $t->foreignId('user_id')->nullable()->index();
            $t->string('ip_address', 45)->nullable();
            $t->text('user_agent')->nullable();
            $t->longText('payload');
            $t->integer('last_activity')->index();
        });

        // Aplikasi yang backupnya disimpan di sini (satu folder per kode di BM_DATA_DIR).
        Schema::create('aplikasi', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 40)->unique();
            $t->string('nama');
            $t->json('rahasia')->nullable();               // tabel yang butuh alasan sebelum dibuka
            $t->string('app_dir')->nullable();
            $t->string('db')->nullable();
            $t->timestamps();
        });

        // Dari mana backup ditarik: VM aplikasi (jenis "aplikasi") atau Backup Master lain (jenis "master").
        Schema::create('sumber', function (Blueprint $t) {
            $t->id();
            $t->string('kode', 40)->unique();              // untuk "aplikasi" = kode aplikasi
            $t->string('nama');
            $t->string('jenis', 10)->default('aplikasi');  // aplikasi | master
            $t->string('host')->nullable();                // kosong = folder lokal di mesin ini
            $t->unsignedSmallInteger('port')->default(22);
            $t->string('pengguna', 60)->default('bmtarik');
            $t->string('folder');                          // akar di sumber (lokal: path penuh; SSH: dibatasi rrsync, jadi "/")
            $t->string('arsip')->nullable();               // subfolder berkas bertanggal
            $t->string('cermin')->nullable();              // subfolder yang dipotret harian
            $t->string('jam', 5)->default('02:30');        // tarik harian setelah jam ini
            $t->boolean('aktif')->default(true);
            $t->boolean('perlu_tarik')->default(false);    // tombol "Tarik sekarang"
            $t->timestamp('terakhir_mulai')->nullable();
            $t->timestamp('terakhir_berhasil')->nullable();
            $t->string('terakhir_status', 10)->nullable(); // ok | gagal
            $t->text('terakhir_pesan')->nullable();
            $t->timestamps();
        });

        // Katalog berkas di BM_DATA_DIR.
        Schema::create('berkas', function (Blueprint $t) {
            $t->id();
            $t->string('aplikasi', 40)->index();
            $t->string('jenis', 10);                       // db | arsip | cermin
            $t->string('path', 500)->unique();             // relatif terhadap BM_DATA_DIR
            $t->string('nama');
            $t->unsignedBigInteger('ukuran')->default(0);
            $t->string('sha256', 64)->nullable();
            $t->dateTime('waktu')->index();                // waktu backup (UTC) dari nama berkas, atau mtime
            $t->string('hari', 10)->index();               // YYYY-MM-DD (zona sekolah) untuk retensi
            $t->boolean('utuh')->nullable();               // hasil cek gzip/isi
            $t->string('pesan')->nullable();
            $t->timestamps();
        });

        Schema::create('tarikan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sumber_id')->index();
            $t->dateTime('mulai');
            $t->dateTime('selesai')->nullable();
            $t->string('status', 10)->default('jalan');    // jalan | ok | gagal
            $t->unsignedInteger('berkas_baru')->default(0);
            $t->unsignedBigInteger('bait')->default(0);
            $t->text('pesan')->nullable();
        });

        // Uji pulih: dump dimuat ke database bmj_<kode>; hasil terakhir sekaligus isi menu Jelajah Data.
        Schema::create('uji_pulih', function (Blueprint $t) {
            $t->id();
            $t->string('aplikasi', 40)->index();
            $t->foreignId('berkas_id')->nullable();
            $t->dateTime('waktu');
            $t->string('status', 10);                      // ok | peringatan | gagal
            $t->unsignedInteger('tabel')->default(0);
            $t->unsignedBigInteger('baris')->default(0);
            $t->json('rincian')->nullable();               // {tabel: jumlah baris}
            $t->text('pesan')->nullable();
        });

        Schema::create('audit_log', function (Blueprint $t) {
            $t->id();
            $t->dateTime('waktu')->index();
            $t->string('aksi');
            $t->string('pelaku')->nullable();
            $t->string('ip', 45)->nullable();
            $t->text('rincian')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['audit_log', 'uji_pulih', 'tarikan', 'berkas', 'sumber', 'aplikasi', 'sessions', 'users'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
