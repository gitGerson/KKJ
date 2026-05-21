<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: A4 landscape;
            margin: 24px 28px 22px;
        }

        body {
            color: #222;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
        }

        .page {
            position: relative;
            width: 100%;
        }

        .brand-logo {
            height: auto;
            left: 44px;
            position: absolute;
            top: 2px;
            width: 92px;
        }

        .title {
            font-size: 10px;
            font-weight: 700;
            line-height: 1.45;
            text-align: center;
        }

        .number {
            color: #8d3030;
            font-size: 12px;
            font-weight: 700;
            margin-top: 6px;
            text-align: center;
        }

        .code-box {
            border: 1px solid #333;
            display: block;
            font-size: 34px;
            font-weight: 700;
            height: 70px;
            position: absolute;
            right: 34px;
            text-align: center;
            top: 0;
            width: 70px;
        }

        .code-box span {
            display: block;
            line-height: 1;
            padding-top: 17px;
            text-align: center;
        }

        .identity {
            margin: 26px 0 16px 44px;
            width: 620px;
        }

        .identity td {
            font-size: 8px;
            font-weight: 700;
            padding: 0 0 10px;
            vertical-align: top;
        }

        .identity .label {
            font-weight: 400;
            width: 190px;
        }

        .identity .separator {
            width: 12px;
        }

        table.members {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        .members th,
        .members td {
            border: 1px solid #555;
            font-size: 6.9px;
            line-height: 1.18;
            padding: 3px 2px;
            text-align: center;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-break: normal;
            word-wrap: break-word;
        }



        /* DomPDF sometimes ignores <colgroup> when the header uses rowspan/colspan.
           This invisible first row forces the real 18 column widths. */
        .members .sizing-row th {
            border: 0 !important;
            font-size: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            overflow: hidden !important;
            padding: 0 !important;
        }

        .members th {
            font-weight: 700;
        }

        .members td {
            height: 18px;
        }

        .members .left {
            text-align: left;
        }

        .members .nowrap {
            overflow-wrap: normal;
            white-space: nowrap;
            word-break: normal;
            word-wrap: normal;
        }

        .members .name-cell {
            font-size: 6.5px;
            line-height: 1.15;
            overflow-wrap: break-word;
            padding-left: 2px;
            padding-right: 2px;
            text-align: left;
            white-space: normal;
            word-break: normal;
            word-wrap: break-word;
        }

        .members .phone-cell {
            font-size: 6.5px;
            overflow-wrap: normal;
            white-space: nowrap;
            word-break: normal;
            word-wrap: normal;
        }

        .members .compact {
            font-size: 5.8px;
            padding-left: 0;
            padding-right: 0;
        }

        .signatures {
            margin-left: auto;
            margin-top: 30px;
            width: 390px;
        }

        .signatures td {
            font-size: 8px;
            text-align: center;
            width: 50%;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin: 44px auto 4px;
            width: 130px;
        }
    </style>
</head>
<body>
    <div class="page">
        @if ($logo)
            <img class="brand-logo" src="{{ $logo }}" alt="Mahanaim">
        @endif

        <div class="title">
            KARTU KELUARGA JEMAAT (KKJ)<br>
            GPdI MAHANAIM TEGAL<br>
            <span style="font-size: 7px;">Jl. Kapten Ismail 137 Tegal Telp. 0283-353039</span>
        </div>

        <div class="number">No: {{ $documentNumber }}</div>
        <div class="code-box"><span>{{ $boxCode }}</span></div>

        <table class="identity">
            <tr>
                <td class="label">Nama Kepala Keluarga Jemaat</td>
                <td class="separator">:</td>
                <td>{{ $kepalaKeluarga?->nama_lengkap ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">Alamat</td>
                <td class="separator">:</td>
                <td>{{ $alamat }}</td>
            </tr>
        </table>

        <table class="members">
            <colgroup>
                <col style="width: 3%;">     {{-- NO --}}
                <col style="width: 28%;">    {{-- NAMA LENGKAP --}}
                <col style="width: 6%;">     {{-- NAMA PANGGILAN --}}
                <col style="width: 9%;">     {{-- NO. HP --}}
                <col style="width: 2.5%;">   {{-- L/P --}}
                <col style="width: 7.95%;">  {{-- TEMPAT --}}
                <col style="width: 2.95%;">  {{-- TGL --}}
                <col style="width: 2.95%;">  {{-- BLN --}}
                <col style="width: 2.95%;">  {{-- TH. --}}
                <col style="width: 1.3%;">   {{-- STATUS A --}}
                <col style="width: 1.3%;">   {{-- STATUS B --}}
                <col style="width: 1.3%;">   {{-- STATUS C --}}
                <col style="width: 6.5%;">   {{-- HUB. KK --}}
                <col style="width: 3.5%;">   {{-- GOL. DAR. --}}
                <col style="width: 4.5%;">   {{-- PEND. TERAKHIR --}}
                <col style="width: 7%;">     {{-- PEKERJAAN --}}
                <col style="width: 3.5%;">   {{-- KEMAH --}}
                <col style="width: 5.8%;">   {{-- DOMISILI --}}
            </colgroup>
            <thead>
                <tr class="sizing-row">
                    <th style="width: 3%;"></th>
                    <th style="width: 28%;"></th>
                    <th style="width: 6%;"></th>
                    <th style="width: 9%;"></th>
                    <th style="width: 2.5%;"></th>
                    <th style="width: 7.95%;"></th>
                    <th style="width: 2.95%;"></th>
                    <th style="width: 2.95%;"></th>
                    <th style="width: 2.95%;"></th>
                    <th style="width: 1.3%;"></th>
                    <th style="width: 1.3%;"></th>
                    <th style="width: 1.3%;"></th>
                    <th style="width: 6.5%;"></th>
                    <th style="width: 3.5%;"></th>
                    <th style="width: 4.5%;"></th>
                    <th style="width: 7%;"></th>
                    <th style="width: 3.5%;"></th>
                    <th style="width: 5.8%;"></th>
                </tr>
                <tr>
                    <th rowspan="3">NO</th>
                    <th class="nowrap" rowspan="3">NAMA LENGKAP</th>
                    <th rowspan="3">NAMA<br>PANGGILAN</th>
                    <th class="nowrap" rowspan="3">NO. HP</th>
                    <th rowspan="3">L/P</th>
                    <th colspan="4">KELAHIRAN</th>
                    <th colspan="3">STATUS</th>
                    <th class="nowrap" rowspan="3">HUB. KK</th>
                    <th rowspan="3">GOL.<br>DAR.</th>
                    <th rowspan="3">PEND.<br>TERAKHIR</th>
                    <th rowspan="3">PEKERJAAN</th>
                    <th rowspan="3">KEMAH</th>
                    <th rowspan="3">DOMISILI</th>
                </tr>
                <tr>
                    <th rowspan="2">TEMPAT</th>
                    <th class="compact" rowspan="2">TGL</th>
                    <th class="compact" rowspan="2">BLN</th>
                    <th class="compact" rowspan="2">TH.</th>
                    <th class="compact">A</th>
                    <th class="compact">B</th>
                    <th class="compact">C</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="name-cell">{{ $row['nama_lengkap'] }}</td>
                        <td>{{ $row['nama_panggilan'] }}</td>
                        <td class="phone-cell">{{ $row['nomor_telepon'] }}</td>
                        <td>{{ $row['jenis_kelamin'] }}</td>
                        <td>{{ $row['tempat_lahir'] }}</td>
                        <td class="compact">{{ $row['tanggal_lahir_tanggal'] }}</td>
                        <td class="compact">{{ $row['tanggal_lahir_bulan'] }}</td>
                        <td class="compact">{{ $row['tanggal_lahir_tahun'] }}</td>
                        <td class="compact">{{ $row['status_menikah'] }}</td>
                        <td class="compact">{{ $row['status_belum_menikah'] }}</td>
                        <td class="compact">{{ $row['status_duda_janda'] }}</td>
                        <td class="nowrap">{{ $row['hub_kk'] }}</td>
                        <td>{{ $row['golongan_darah'] }}</td>
                        <td>{{ $row['pendidikan'] }}</td>
                        <td>{{ $row['pekerjaan'] }}</td>
                        <td>{{ $row['kemah'] }}</td>
                        <td>{{ $row['domisili'] }}</td>
                    </tr>
                @endforeach

                @for ($number = $rows->count() + 1; $number <= 10; $number++)
                    <tr>
                        <td>{{ $number }}</td>
                        @for ($column = 1; $column < 18; $column++)
                            <td></td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>

        <table class="signatures">
            <tr>
                <td>Mengetahui,</td>
                <td>Tegal, {{ now()->translatedFormat('j F Y') }}</td>
            </tr>
            <tr>
                <td>
                    <div class="signature-line"></div>
                    Pdm. Stevan R. Pioh<br>
                    Gembala Area
                </td>
                <td>
                    <div class="signature-line"></div>
                    Kepala Keluarga
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
