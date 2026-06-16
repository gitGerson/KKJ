<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Umat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class KeluargaCardPdf
{
    /**
     * @return non-empty-string
     */
    public function render(Keluarga $keluarga): string
    {
        $umat = $keluarga->umat->values();
        $area = $umat->pluck('area.name')->filter()->unique()->join(', ') ?: '-';
        $kemah = $umat->pluck('kemah.name')->filter()->unique()->join(', ') ?: '-';
        $gembalaArea = $this->gembalaArea($umat);

        return Pdf::loadView('pdf.keluarga-card', [
            'keluarga' => $keluarga,
            'umat' => $umat,
            'rows' => $this->rows($umat),
            'alamat' => $this->firstFilled($umat, 'alamat') ?? '-',
            'area' => $area,
            'kemah' => $kemah,
            'boxCode' => 'A2',
            'documentNumber' => $this->documentNumber($keluarga, $area),
            'kepalaKeluarga' => $this->kepalaKeluarga($umat),
            'logo' => $this->logo(),
            'gembala' => $gembalaArea?->gembala ?: 'Pdm. Stevan R. Pioh',
            'ttdGembala' => $this->ttdImage($gembalaArea),
        ])
            ->setPaper('a4', 'landscape')
            ->output();
    }

    /**
     * @param  Collection<int, Umat>  $umat
     * @return Collection<int, array{
     *     nama_lengkap: string|null,
     *     nama_panggilan: string|null,
     *     nomor_telepon: string|null,
     *     jenis_kelamin: string|null,
     *     tempat_lahir: string|null,
     *     tanggal_lahir_tanggal: string|null,
     *     tanggal_lahir_bulan: string|null,
     *     tanggal_lahir_tahun: string|null,
     *     status_menikah: string,
     *     status_belum_menikah: string,
     *     status_duda_janda: string,
     *     hub_kk: string|null,
     *     golongan_darah: string|null,
     *     pendidikan: string|null,
     *     pekerjaan: string|null,
     *     kemah: string|null,
     *     domisili: string|null
     * }>
     */
    private function rows(Collection $umat): Collection
    {
        return $umat->map(fn (Umat $member): array => [
            'nama_lengkap' => $member->nama_lengkap,
            'nama_panggilan' => $member->nama_panggilan,
            'nomor_telepon' => $member->nomor_telepon,
            'jenis_kelamin' => $member->jenis_kelamin,
            'tempat_lahir' => $member->tempat_lahir,
            'tanggal_lahir_tanggal' => $member->tanggal_lahir?->format('j'),
            'tanggal_lahir_bulan' => $member->tanggal_lahir?->format('n'),
            'tanggal_lahir_tahun' => $member->tanggal_lahir?->format('Y'),
            'status_menikah' => $this->statusMark($member, 'menikah'),
            'status_belum_menikah' => $this->statusMark($member, 'belum_menikah'),
            'status_duda_janda' => $this->statusMark($member, 'duda_janda'),
            'hub_kk' => $member->hub_kk,
            'golongan_darah' => $member->golongan_darah,
            'pendidikan' => $member->pendidikan,
            'pekerjaan' => $member->pekerjaan,
            'kemah' => $member->kemah?->name,
            'domisili' => $member->domisili,
        ]);
    }

    /**
     * @param  Collection<int, Umat>  $umat
     */
    private function kepalaKeluarga(Collection $umat): ?Umat
    {
        return $umat->first(fn (Umat $umat): bool => $umat->hub_kk === 'Kepala Keluarga')
            ?? $umat->first();
    }

    /**
     * Area dari kepala keluarga (atau anggota pertama yang punya area) untuk tanda tangan gembala.
     *
     * @param  Collection<int, Umat>  $umat
     */
    private function gembalaArea(Collection $umat): ?Area
    {
        return $this->kepalaKeluarga($umat)?->area
            ?? $umat->firstWhere(fn (Umat $member): bool => $member->area !== null)?->area;
    }

    private function ttdImage(?Area $area): ?string
    {
        if ($area?->ttd_path === null) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($area->ttd_path)) {
            return null;
        }

        $contents = (string) $disk->get($area->ttd_path);
        $mime = $disk->mimeType($area->ttd_path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($contents);
    }

    private function documentNumber(Keluarga $keluarga, string $area): string
    {
        return $keluarga->no_keluarga.'/'.$area.'/'.now()->format('m-Y');
    }

    private function logo(): ?string
    {
        $path = public_path('logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    private function statusMark(Umat $umat, string $statusColumn): string
    {
        $status = mb_strtolower((string) $umat->status_perkawinan);

        return match ($statusColumn) {
            'menikah' => str_contains($status, 'nikah') && ! str_contains($status, 'belum') ? 'v' : '',
            'belum_menikah' => str_contains($status, 'belum') ? 'v' : '',
            'duda_janda' => str_contains($status, 'duda') || str_contains($status, 'janda') ? 'v' : '',
            default => '',
        };
    }

    /**
     * @param  Collection<int, Umat>  $umat
     */
    private function firstFilled(Collection $umat, string $field): ?string
    {
        return $umat
            ->pluck($field)
            ->filter(fn (?string $value): bool => filled($value))
            ->first();
    }
}
