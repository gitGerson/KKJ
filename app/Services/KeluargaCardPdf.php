<?php

namespace App\Services;

use App\Models\Keluarga;
use App\Models\Umat;
use Illuminate\Support\Collection;

class KeluargaCardPdf
{
    private const PAGE_WIDTH = 842;

    private const PAGE_HEIGHT = 595;

    private const MARGIN = 32;

    /**
     * @return non-empty-string
     */
    public function render(Keluarga $keluarga): string
    {
        $pages = [];
        $content = $this->startPage($keluarga);
        $y = 402;

        foreach ($keluarga->umat as $index => $umat) {
            if ($y < 78) {
                $pages[] = $content;
                $content = $this->startPage($keluarga, true);
                $y = 402;
            }

            $content .= $this->row($umat, $index + 1, $y);
            $y -= 42;
        }

        if ($keluarga->umat->isEmpty()) {
            $content .= $this->text('No umat members registered.', 44, $y - 24, 9);
        }

        $pages[] = $content;

        return $this->document($pages);
    }

    private function startPage(Keluarga $keluarga, bool $continued = false): string
    {
        $alamat = $this->firstFilled($keluarga->umat, 'alamat') ?? '-';
        $area = $keluarga->umat->pluck('area.name')->filter()->unique()->join(', ') ?: '-';
        $kemah = $keluarga->umat->pluck('kemah.name')->filter()->unique()->join(', ') ?: '-';

        $content = '';
        $content .= $this->text('KARTU KELUARGA UMAT', 287, 548, 17, true);
        $content .= $this->text('NO. KKJ: '.$keluarga->no_keluarga.($continued ? ' (LANJUTAN)' : ''), 330, 526, 11, true);
        $content .= $this->line(32, 512, 810, 512, 1.2);

        $content .= $this->labelValue('Area', $area, 44, 488);
        $content .= $this->labelValue('Kemah', $kemah, 312, 488);
        $content .= $this->labelValue('Jumlah Umat', (string) $keluarga->umat->count(), 548, 488);
        $content .= $this->labelValue('Alamat', $alamat, 44, 468, 690);

        $content .= $this->text('DATA ANGGOTA KELUARGA', 44, 436, 10, true);
        $content .= $this->tableHeader(420);

        return $content;
    }

    private function labelValue(string $label, string $value, int $x, int $y, int $valueWidth = 150): string
    {
        return $this->text($label, $x, $y, 8, true)
            .$this->text(':', $x + 58, $y, 8)
            .$this->wrappedText($value, $x + 68, $y, $valueWidth, 8, 10, 2);
    }

    private function tableHeader(int $y): string
    {
        $content = $this->rect(32, $y - 24, 778, 24, true);

        foreach ($this->columns() as $column) {
            $content .= $this->text($column['label'], $column['x'] + 3, $y - 15, 6.5, true);
            $content .= $this->line($column['x'], $y, $column['x'], 42, 0.4);
        }

        return $content.$this->line(810, $y, 810, 42, 0.4);
    }

    private function row(Umat $umat, int $number, int $y): string
    {
        $content = $this->rect(32, $y - 42, 778, 42);
        $values = [
            (string) $number,
            $umat->nama_lengkap,
            $umat->jenis_kelamin,
            $umat->status_perkawinan,
            $umat->hub_kk,
            $umat->golongan_darah,
            trim(($umat->tempat_lahir ?: '-').' / '.($umat->tanggal_lahir?->format('d-m-Y') ?: '-')),
            $umat->nomor_telepon,
            $umat->pendidikan,
            $umat->pekerjaan,
            $umat->domisili,
        ];

        foreach ($this->columns() as $index => $column) {
            $content .= $this->wrappedText((string) ($values[$index] ?: '-'), $column['x'] + 3, $y - 11, $column['width'] - 6, 6.2, 8.4, 4);
        }

        return $content;
    }

    /**
     * @return array<int, array{x: int, width: int, label: string}>
     */
    private function columns(): array
    {
        return [
            ['x' => 32, 'width' => 24, 'label' => 'NO'],
            ['x' => 56, 'width' => 154, 'label' => 'NAMA LENGKAP'],
            ['x' => 210, 'width' => 28, 'label' => 'P/L'],
            ['x' => 238, 'width' => 54, 'label' => 'STATUS'],
            ['x' => 292, 'width' => 62, 'label' => 'HUB KK'],
            ['x' => 354, 'width' => 38, 'label' => 'GOL DAR'],
            ['x' => 392, 'width' => 96, 'label' => 'TMP / TGL LAHIR'],
            ['x' => 488, 'width' => 80, 'label' => 'HP'],
            ['x' => 568, 'width' => 72, 'label' => 'PENDIDIKAN'],
            ['x' => 640, 'width' => 88, 'label' => 'PEKERJAAN'],
            ['x' => 728, 'width' => 82, 'label' => 'DOMISILI'],
        ];
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

    private function document(array $pages): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids ['.collect(range(0, count($pages) - 1))->map(fn (int $index): string => ($index * 2 + 3).' 0 R')->join(' ').'] /Count '.count($pages).' >>',
        ];

        foreach ($pages as $index => $pageContent) {
            $pageObject = count($objects) + 1;
            $contentObject = $pageObject + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] /Resources << /Font << /F1 '.(count($pages) * 2 + 3).' 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[] = '<< /Length '.strlen($pageContent)." >>\nstream\n".$pageContent."\nendstream";
        }

        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function text(string $value, int|float $x, int|float $y, int|float $size = 8, bool $bold = false): string
    {
        $weight = $bold ? '0.35 Tr' : '0 Tr';

        return "BT /F1 {$size} Tf {$weight} {$x} {$y} Td (".$this->escape($value).") Tj ET\n";
    }

    private function wrappedText(string $value, int|float $x, int|float $y, int $width, float $size, float $lineHeight, int $maxLines): string
    {
        $words = preg_split('/\s+/', trim($value)) ?: [];
        $lines = [];
        $current = '';
        $maxCharacters = max(1, (int) floor($width / ($size * 0.52)));

        foreach ($words as $word) {
            $candidate = trim($current.' '.$word);

            if (strlen($candidate) > $maxCharacters && $current !== '') {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }

            if (count($lines) === $maxLines) {
                break;
            }
        }

        if ($current !== '' && count($lines) < $maxLines) {
            $lines[] = $current;
        }

        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
        }

        $content = '';

        foreach ($lines as $index => $line) {
            $content .= $this->text($line, $x, $y - ($index * $lineHeight), $size);
        }

        return $content;
    }

    private function rect(int|float $x, int|float $y, int|float $width, int|float $height, bool $filled = false): string
    {
        return $filled
            ? "q 0.92 0.92 0.92 rg {$x} {$y} {$width} {$height} re f Q\n".$this->rect($x, $y, $width, $height)
            : "q 0 G 0.45 w {$x} {$y} {$width} {$height} re S Q\n";
    }

    private function line(int|float $x1, int|float $y1, int|float $x2, int|float $y2, float $width = 0.5): string
    {
        return "q 0 G {$width} w {$x1} {$y1} m {$x2} {$y2} l S Q\n";
    }

    private function escape(string $value): string
    {
        $value = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')'], ['\\\\', '\(', '\)'], $value);
    }
}
