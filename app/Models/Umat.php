<?php

namespace App\Models;

use Database\Factories\UmatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'nama_lengkap',
    'nama_panggilan',
    'nomor_telepon',
    'jenis_kelamin',
    'status_perkawinan',
    'hub_kk',
    'golongan_darah',
    'tempat_lahir',
    'tanggal_lahir',
    'alamat',
    'kemah_id',
    'area_id',
    'pendidikan',
    'pekerjaan',
    'domisili',
    'keluarga_id',
])]
class Umat extends Model
{
    /** @use HasFactory<UmatFactory> */
    use HasFactory;

    protected $table = 'umat';

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function kemah(): BelongsTo
    {
        return $this->belongsTo(Kemah::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }
}
