<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Keluarga;
use App\Models\Kemah;
use App\Models\Umat;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class KeluargaExcelImporter
{
    /**
     * @return array{keluarga: int, umat: int}
     */
    public function import(string $path): array
    {
        $rows = $this->rows($path);

        $imported = [
            'keluarga' => 0,
            'umat' => 0,
        ];

        DB::transaction(function () use ($rows, &$imported): void {
            foreach ($rows as $row) {
                if (blank($row['no_keluarga']) || blank($row['nama_lengkap'])) {
                    continue;
                }

                $keluarga = Keluarga::query()->firstOrCreate([
                    'no_keluarga' => $row['no_keluarga'],
                ]);

                if ($keluarga->wasRecentlyCreated) {
                    $imported['keluarga']++;
                }

                Umat::query()->updateOrCreate(
                    [
                        'keluarga_id' => $keluarga->id,
                        'nama_lengkap' => $row['nama_lengkap'],
                    ],
                    [
                        'nama_panggilan' => $row['nama_panggilan'],
                        'nomor_telepon' => $row['nomor_telepon'],
                        'jenis_kelamin' => $row['jenis_kelamin'],
                        'status_perkawinan' => $row['status_perkawinan'],
                        'hub_kk' => $row['hub_kk'],
                        'golongan_darah' => $row['golongan_darah'],
                        'tempat_lahir' => $row['tempat_lahir'],
                        'tanggal_lahir' => $row['tanggal_lahir'],
                        'alamat' => $row['alamat'],
                        'kemah_id' => $this->findOrCreateKemahId($row['kemah']),
                        'area_id' => $this->findOrCreateAreaId($row['area']),
                        'pendidikan' => $row['pendidikan'],
                        'pekerjaan' => $row['pekerjaan'],
                        'domisili' => $row['domisili'],
                    ]
                );

                $imported['umat']++;
            }
        });

        return $imported;
    }

    private function findOrCreateAreaId(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        return Area::query()->firstOrCreate(['name' => $name])->id;
    }

    private function findOrCreateKemahId(?string $name): ?int
    {
        if (blank($name)) {
            return null;
        }

        return Kemah::query()->firstOrCreate(['name' => $name])->id;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rows(string $path): array
    {
        $workbook = $this->openWorkbook($path);
        $sharedStrings = $this->sharedStrings($workbook);
        $sheet = $this->sheet($workbook);

        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $rowIndex = (int) $row['r'];

            if ($rowIndex === 1) {
                continue;
            }

            $cells = $this->cells($row, $sharedStrings);

            if ($this->isEmptyRow($cells)) {
                continue;
            }

            $rows[] = [
                'area' => $this->value($cells, 'A'),
                'no_keluarga' => $this->value($cells, 'B'),
                'nama_lengkap' => $this->value($cells, 'D'),
                'nama_panggilan' => $this->value($cells, 'E'),
                'nomor_telepon' => $this->value($cells, 'F'),
                'jenis_kelamin' => $this->gender($this->value($cells, 'G')),
                'status_perkawinan' => $this->value($cells, 'H'),
                'hub_kk' => $this->hubunganKeluarga($this->value($cells, 'I')),
                'golongan_darah' => $this->value($cells, 'J'),
                'tempat_lahir' => $this->value($cells, 'K'),
                'tanggal_lahir' => $this->date($this->value($cells, 'L'), $this->value($cells, 'M'), $this->value($cells, 'N')),
                'alamat' => $this->value($cells, 'O'),
                'kemah' => $this->value($cells, 'P'),
                'pendidikan' => $this->value($cells, 'Q'),
                'pekerjaan' => $this->value($cells, 'R'),
                'domisili' => $this->value($cells, 'S'),
            ];
        }

        $workbook->close();

        return $rows;
    }

    private function openWorkbook(string $path): ZipArchive
    {
        $workbook = new ZipArchive;

        if ($workbook->open($path) !== true) {
            throw new RuntimeException('Unable to open the Excel file.');
        }

        return $workbook;
    }

    /**
     * @return array<int, string>
     */
    private function sharedStrings(ZipArchive $workbook): array
    {
        $xml = $workbook->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = [];

        foreach (new SimpleXMLElement($xml) as $item) {
            $parts = [];

            foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $text) {
                $parts[] = (string) $text;
            }

            $strings[] = implode('', $parts);
        }

        return $strings;
    }

    private function sheet(ZipArchive $workbook): SimpleXMLElement
    {
        $xml = $workbook->getFromName('xl/worksheets/sheet1.xml');

        if ($xml === false) {
            throw new RuntimeException('The Excel file does not contain a first worksheet.');
        }

        return new SimpleXMLElement($xml);
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<string, string>
     */
    private function cells(SimpleXMLElement $row, array $sharedStrings): array
    {
        $cells = [];

        foreach ($row->c as $cell) {
            $reference = (string) $cell['r'];
            $column = preg_replace('/\d+/', '', $reference);

            if (! is_string($column) || $column === '') {
                continue;
            }

            $rawValue = isset($cell->v) ? (string) $cell->v : '';
            $type = (string) $cell['t'];

            $cells[$column] = match ($type) {
                's' => $sharedStrings[(int) $rawValue] ?? '',
                'inlineStr' => (string) ($cell->is->t ?? ''),
                default => $rawValue,
            };
        }

        return $cells;
    }

    /**
     * @param  array<string, string>  $cells
     */
    private function isEmptyRow(array $cells): bool
    {
        return collect($cells)->filter(fn (string $value): bool => filled(trim($value)))->isNotEmpty() === false;
    }

    /**
     * @param  array<string, string>  $cells
     */
    private function value(array $cells, string $column): ?string
    {
        $value = trim($cells[$column] ?? '');

        return $value === '' ? null : $value;
    }

    private function gender(?string $value): ?string
    {
        $value = strtoupper((string) $value);

        return in_array($value, ['L', 'P'], true) ? $value : null;
    }

    private function hubunganKeluarga(?string $value): ?string
    {
        return match (strtoupper((string) $value)) {
            'KK', 'KEPALA KELUARGA' => 'Kepala Keluarga',
            'ISTRI' => 'Istri',
            'ANAK' => 'Anak',
            default => $value,
        };
    }

    private function date(?string $day, ?string $month, ?string $year): ?string
    {
        $day = (int) $day;
        $month = (int) $month;
        $year = (int) $year;

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return (new DateTimeImmutable("{$year}-{$month}-{$day}"))->format('Y-m-d');
    }
}
