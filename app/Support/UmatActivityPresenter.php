<?php

namespace App\Support;

use App\Models\Area;
use Spatie\Activitylog\Models\Activity;

class UmatActivityPresenter
{
    /**
     * Cache id area -> nama agar tidak query berulang dalam satu request.
     *
     * @var array<int, string>|null
     */
    private static ?array $areaNames = null;

    /**
     * Daftar perubahan field beserta nilai lama -> baru untuk satu aktivitas.
     *
     * @return list<array{label: string, from: string, to: string}>
     */
    public static function changes(Activity $activity): array
    {
        // Hanya event 'updated' yang menampilkan rincian dari -> ke.
        if ($activity->event !== 'updated') {
            return [];
        }

        $new = (array) ($activity->attribute_changes?->get('attributes') ?? []);
        $old = (array) ($activity->attribute_changes?->get('old') ?? []);

        $lines = [];

        foreach (array_keys($new) as $field) {
            $lines[] = [
                'label' => self::label($field),
                'from' => self::value($field, $old[$field] ?? null),
                'to' => self::value($field, $new[$field] ?? null),
            ];
        }

        return $lines;
    }

    private static function label(string $field): string
    {
        return match ($field) {
            'nama_lengkap' => __('Name'),
            'alamat' => __('Alamat'),
            'area_id' => __('Area'),
            'status' => __('Status keanggotaan'),
            'keterangan' => __('Keterangan'),
            default => $field,
        };
    }

    private static function value(string $field, mixed $raw): string
    {
        if ($raw === null || $raw === '') {
            return '—';
        }

        return match ($field) {
            'area_id' => self::areaName((int) $raw),
            'status' => __(ucfirst((string) $raw)),
            default => (string) $raw,
        };
    }

    private static function areaName(int $id): string
    {
        self::$areaNames ??= Area::query()->pluck('name', 'id')->all();

        return self::$areaNames[$id] ?? (__('Area').' #'.$id);
    }
}
