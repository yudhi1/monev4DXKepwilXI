<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\LagMeasure;
use App\Models\LeadMeasure;
use App\Models\User;
use App\Models\Wig;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $wilayah = Wilayah::firstOrCreate(['kode' => 'W-XI'], ['nama' => 'Kedeputian Wilayah XI']);

        $cabangs = [
            ['kode' => 'CB-001', 'nama' => 'Cabang Jakarta'],
            ['kode' => 'CB-002', 'nama' => 'Cabang Bandung'],
            ['kode' => 'CB-003', 'nama' => 'Cabang Surabaya'],
        ];
        foreach ($cabangs as $c) {
            Cabang::firstOrCreate(['kode' => $c['kode']], ['wilayah_id' => $wilayah->id, 'nama' => $c['nama']]);
        }

        $admin = User::firstOrCreate(['email' => 'admin@monev.test'], [
            'name' => 'Administrator',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $admin->syncRoles(['admin']);

        $wilUser = User::firstOrCreate(['email' => 'wilayah@monev.test'], [
            'name' => 'User Wilayah XI',
            'password' => Hash::make('password'),
            'wilayah_id' => $wilayah->id,
            'is_active' => true,
        ]);
        $wilUser->syncRoles(['kedeputian_wilayah']);

        $cab1 = Cabang::where('kode', 'CB-001')->first();
        $cabUser = User::firstOrCreate(['email' => 'cabang@monev.test'], [
            'name' => 'User Cabang Jakarta',
            'password' => Hash::make('password'),
            'wilayah_id' => $wilayah->id,
            'cabang_id' => $cab1->id,
            'is_active' => true,
        ]);
        $cabUser->syncRoles(['kantor_cabang']);

        $tahun = (int) date('Y');

        $wig = Wig::firstOrCreate(['kode_wig' => 'WIG-'.$tahun.'-01'], [
            'nama_wig' => 'Peningkatan Kinerja Operasional',
            'indikator_output' => 'Pertumbuhan pelayanan minimal 15%',
            'tahun' => $tahun,
            'wilayah_id' => $wilayah->id,
            'created_by' => $wilUser->id,
        ]);

        $lag = LagMeasure::firstOrCreate(['kode_lag' => 'LAG-'.$tahun.'-01'], [
            'wig_id' => $wig->id,
            'nama_lag' => 'Total pelayanan tercapai',
            'target_tahunan' => 1000,
            'satuan' => 'transaksi',
            'tahun' => $tahun,
        ]);

        LeadMeasure::firstOrCreate(['kode_lead' => 'LEAD-'.$tahun.'-01'], [
            'lag_measure_id' => $lag->id,
            'wig_id' => $wig->id,
            'nama_lead' => 'Kunjungan harian ke nasabah',
            'satuan' => 'kunjungan',
            'tahun' => $tahun,
        ]);
    }
}
