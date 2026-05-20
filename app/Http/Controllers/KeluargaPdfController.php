<?php

namespace App\Http\Controllers;

use App\Models\Keluarga;
use App\Services\KeluargaCardPdf;
use Illuminate\Http\Response;

class KeluargaPdfController extends Controller
{
    public function __invoke(Keluarga $keluarga, KeluargaCardPdf $pdf): Response
    {
        $keluarga->load(['umat' => fn ($query) => $query
            ->with(['area', 'kemah'])
            ->orderByRaw("CASE hub_kk WHEN 'Kepala Keluarga' THEN 1 WHEN 'Istri' THEN 2 WHEN 'Anak' THEN 3 ELSE 4 END")
            ->orderBy('nama_lengkap'),
        ]);

        return response($pdf->render($keluarga), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="kartu-keluarga-'.$keluarga->no_keluarga.'.pdf"',
        ]);
    }
}
