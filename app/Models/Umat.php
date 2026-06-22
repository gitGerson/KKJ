<?php

namespace App\Models;

use Database\Factories\UmatFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

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
    'status',
    'tanggal_masuk',
    'tanggal_keluar',
    'keterangan',
])]
class Umat extends Model
{
    /** @use HasFactory<UmatFactory> */
    use HasFactory, LogsActivity;

    protected $table = 'umat';

    /**
     * Catat perubahan penting jemaat untuk riwayat di dashboard.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama_lengkap', 'alamat', 'area_id', 'status', 'keterangan'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event): string => match ($event) {
                'created' => 'Jemaat baru ditambahkan',
                'updated' => 'Data jemaat diperbarui',
                'deleted' => 'Data jemaat dihapus',
                default => $event,
            });
    }

    public const STATUS_CALON = 'calon';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_KELUAR = 'keluar';

    public const STATUS_MENINGGAL = 'meninggal';

    /**
     * Status yang tampil di list utama (belum diarsip).
     *
     * @var list<string>
     */
    public const STATUS_LIST_UTAMA = [self::STATUS_CALON, self::STATUS_AKTIF];

    /**
     * Status yang dianggap arsip.
     *
     * @var list<string>
     */
    public const STATUS_ARSIP = [self::STATUS_KELUAR, self::STATUS_MENINGGAL];

    /**
     * Semua status yang valid.
     *
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_CALON, self::STATUS_AKTIF, self::STATUS_KELUAR, self::STATUS_MENINGGAL];
    }

    public const KELOMPOK_ANAK = 'anak';

    public const KELOMPOK_REMAJA = 'remaja';

    public const KELOMPOK_PEMUDA = 'pemuda';

    public const KELOMPOK_DEWASA = 'dewasa';

    /**
     * Batas usia (dalam tahun, inklusif) per kelompok. Batas atas null = tak terbatas.
     *
     * @var array<string, array{int, int|null}>
     */
    public const KELOMPOK_USIA = [
        self::KELOMPOK_ANAK => [0, 12],
        self::KELOMPOK_REMAJA => [13, 17],
        self::KELOMPOK_PEMUDA => [18, 30],
        self::KELOMPOK_DEWASA => [31, null],
    ];

    /**
     * @return list<string>
     */
    public static function kelompokUsiaList(): array
    {
        return array_keys(self::KELOMPOK_USIA);
    }

    /**
     * Distribusi jumlah jemaat aktif/calon per kelompok usia.
     *
     * @return array<string, int>
     */
    public static function demografiUsia(): array
    {
        $hasil = [];

        foreach (self::kelompokUsiaList() as $kelompok) {
            $hasil[$kelompok] = self::query()
                ->whereIn('status', self::STATUS_LIST_UTAMA)
                ->kelompokUsia($kelompok)
                ->count();
        }

        return $hasil;
    }

    /**
     * Calon yang sudah dipantau minimal 6 bulan (siap dipromosikan ke aktif).
     */
    public function scopeCalonMatang(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_CALON)
            ->whereNotNull('tanggal_masuk')
            ->whereDate('tanggal_masuk', '<=', now()->subMonths(6)->toDateString());
    }

    /**
     * Jemaat yang berulang tahun pada bulan tertentu (1-12).
     */
    public function scopeUlangTahunBulan(Builder $query, int $bulan): Builder
    {
        return $query->whereNotNull('tanggal_lahir')->whereMonth('tanggal_lahir', $bulan);
    }

    /**
     * Saring berdasarkan kelompok usia, diterjemahkan ke rentang tanggal lahir.
     */
    public function scopeKelompokUsia(Builder $query, string $kelompok): Builder
    {
        $range = self::KELOMPOK_USIA[$kelompok] ?? null;

        if ($range === null) {
            return $query;
        }

        [$usiaMin, $usiaMax] = $range;
        $today = now()->startOfDay();

        $query->whereNotNull('tanggal_lahir')
            ->whereDate('tanggal_lahir', '<=', $today->subYears($usiaMin)->toDateString());

        if ($usiaMax !== null) {
            $query->whereDate('tanggal_lahir', '>=', $today->subYears($usiaMax + 1)->addDay()->toDateString());
        }

        return $query;
    }

    /**
     * Saring berdasarkan area.
     */
    public function scopeArea(Builder $query, int $areaId): Builder
    {
        return $query->where('area_id', $areaId);
    }

    /**
     * Umur saat ini (tahun penuh), null bila tanggal lahir kosong.
     */
    protected function umur(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->tanggal_lahir?->age);
    }

    /**
     * Kelompok usia (anak/remaja/pemuda/dewasa), null bila tanggal lahir kosong.
     */
    protected function kelompokUsia(): Attribute
    {
        return Attribute::get(function (): ?string {
            $umur = $this->umur;

            if ($umur === null) {
                return null;
            }

            foreach (self::KELOMPOK_USIA as $nama => [$min, $max]) {
                if ($umur >= $min && ($max === null || $umur <= $max)) {
                    return $nama;
                }
            }

            return null;
        });
    }

    /**
     * Sebutan otomatis (Anak/Sdr/Sdri/Bapak/Ibu) dari usia, gender, dan status nikah.
     */
    protected function pemanggilan(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->umur !== null && $this->umur <= 13) {
                return 'Anak';
            }

            $pria = $this->jenis_kelamin === 'L';
            $sudahMenikah = $this->status_perkawinan !== null && $this->status_perkawinan !== 'Belum Kawin';

            if ($sudahMenikah) {
                return $pria ? 'Bapak' : 'Ibu';
            }

            return $pria ? 'Sdr' : 'Sdri';
        });
    }

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
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
        ];
    }
}
