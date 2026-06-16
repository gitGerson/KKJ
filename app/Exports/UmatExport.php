<?php

namespace App\Exports;

use App\Models\Umat;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UmatExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private Builder $query) {}

    public function query(): Builder
    {
        return $this->query->with(['area', 'kemah', 'keluarga']);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'area',
            'no_keluarga',
            'nama_lengkap',
            'nama_panggilan',
            'nomor_telepon',
            'jenis_kelamin',
            'status_perkawinan',
            'hub_kk',
            'pemanggilan',
            'golongan_darah',
            'tempat_lahir',
            'tanggal_lahir',
            'umur',
            'kelompok_usia',
            'alamat',
            'kemah',
            'pendidikan',
            'pekerjaan',
            'domisili',
            'status',
            'tanggal_masuk',
            'tanggal_keluar',
            'keterangan',
        ];
    }

    /**
     * @param  Umat  $umat
     * @return list<mixed>
     */
    public function map($umat): array
    {
        return [
            $umat->area?->name,
            $umat->keluarga?->no_keluarga,
            $umat->nama_lengkap,
            $umat->nama_panggilan,
            $umat->nomor_telepon,
            $umat->jenis_kelamin,
            $umat->status_perkawinan,
            $umat->hub_kk,
            $umat->pemanggilan,
            $umat->golongan_darah,
            $umat->tempat_lahir,
            $umat->tanggal_lahir?->format('Y-m-d'),
            $umat->umur,
            $umat->kelompok_usia,
            $umat->alamat,
            $umat->kemah?->name,
            $umat->pendidikan,
            $umat->pekerjaan,
            $umat->domisili,
            $umat->status,
            $umat->tanggal_masuk?->format('Y-m-d'),
            $umat->tanggal_keluar?->format('Y-m-d'),
            $umat->keterangan,
        ];
    }
}
