<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HistoryController extends Controller
{
    private $spreadsheetId = '1tRA1dcU208Fdw3BGHnaLB6jCtBGRHJEIiOH7GmFkoP8';

    public function index(Request $request)
    {
        // =========================================================
        // Filter params
        // =========================================================
        $search      = $request->get('search', '');
        $filterSheet = $request->get('sheet', '');
        $filterField = $request->get('field', '');
        $dateFrom    = $request->get('date_from', '');
        $dateTo      = $request->get('date_to', '');

        // =========================================================
        // Ambil data dari sheet History (cache 1 menit)
        // =========================================================
        $raw = Cache::remember('history_cache', 1, function () {

            $client = new \Google_Client();
            $client->setApplicationName('Battle Map');
            $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
            $client->setAuthConfig($credentials);
            $client->addScope(\Google_Service_Sheets::SPREADSHEETS_READONLY);

            $service  = new \Google_Service_Sheets($client);
            $response = $service->spreadsheets_values->get(
                $this->spreadsheetId,
                'History!A:H'
            );

            $values = $response->getValues() ?? [];

            if (empty($values)) return [];

            // Baris pertama = header, skip
            $rows = [];
            foreach (array_slice($values, 1) as $row) {
                $rows[] = [
                    'timestamp'   => $row[0] ?? '',
                    'sheet'       => $row[1] ?? '',
                    'row_id'      => $row[2] ?? '',
                    'nama_lokasi' => $row[3] ?? '',
                    'field'       => $row[4] ?? '',
                    'old_value'   => $row[5] ?? '',
                    'new_value'   => $row[6] ?? '',
                    'user'        => $row[7] ?? '—',
                ];
            }

            // Urutkan terbaru di atas
            return array_reverse($rows);
        });

        // =========================================================
        // Apply filter
        // =========================================================
        $filtered = collect($raw)->filter(function ($row) use ($search, $filterSheet, $filterField, $dateFrom, $dateTo) {
            if ($filterSheet && $row['sheet'] !== $filterSheet) return false;
            if ($filterField && $row['field'] !== $filterField) return false;

            // Filter tanggal dari timestamp (format: d/m/Y H:i:s)
            if ($dateFrom || $dateTo) {
                $parts     = explode(' ', $row['timestamp']);
                $dateParts = explode('/', $parts[0] ?? '');
                if (count($dateParts) !== 3) return false;

                $rowDate  = $dateParts[2] . '-' . $dateParts[1] . '-' . $dateParts[0];
                $checkFrom = $dateFrom ?: '0000-00-00';
                $checkTo   = $dateTo   ?: '9999-99-99';

                if ($rowDate < $checkFrom || $rowDate > $checkTo) return false;
            }

            if ($search) {
                $q = strtolower($search);
                return str_contains(strtolower($row['nama_lokasi']), $q)
                    || str_contains(strtolower($row['old_value']), $q)
                    || str_contains(strtolower($row['new_value']), $q);
            }
            return true;
        })->values();

        // Opsi untuk dropdown filter
        $sheetOptions = collect($raw)->pluck('sheet')->unique()->sort()->values();
        $fieldOptions = collect($raw)->pluck('field')->unique()->sort()->values();

        return view('history', [
            'rows'         => $filtered,
            'search'       => $search,
            'filterSheet'  => $filterSheet,
            'filterField'  => $filterField,
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
            'sheetOptions' => $sheetOptions,
            'fieldOptions' => $fieldOptions,
        ]);
    }
}