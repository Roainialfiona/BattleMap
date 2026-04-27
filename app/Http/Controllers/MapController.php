<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{

    private $spreadsheetId = '1tRA1dcU208Fdw3BGHnaLB6jCtBGRHJEIiOH7GmFkoP8';

    public function index()
    {

        $data = Cache::remember('battle_map_cache', 5, function () {

            $client = new \Google_Client();
            $client->setApplicationName('Battle Map');
            $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
            $client->setAuthConfig($credentials);
            $client->addScope(\Google_Service_Sheets::SPREADSHEETS);

            $service = new \Google_Service_Sheets($client);

            $data = [
                'education' => [],
                'sppg'      => [],
                'kdmp'      => [],
                'faskes'    => [],
                'hotel'     => [],
                'bank'      => [],
                'wisata'    => [],
                'koperasi'  => []
            ];

            $sheets = [
                'New_Education',
                'New_SPPG',
                'New_KDMP',
                'New_Faskes',
                'New_Hotel',
                'New_Wisata',
                'New_Bank',
                'New_Koperasi'
            ];

            $sheetConfig = [
                'New_Education' => [
                    'nama' => 'nama satuan pendidikan',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_SPPG' => [
                    'nama' => 'nama sppg',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_KDMP' => [
                    'nama' => 'nama kdmp',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_Faskes' => [
                    'nama' => 'nama faskes',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_Hotel' => [
                    'nama' => 'nama hotel',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_Wisata' => [
                    'nama' => 'nama wisata',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_Bank' => [
                    'nama' => 'nama bank',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
                'New_Koperasi' => [
                    'nama' => 'nama koperasi',
                    'lat'  => 'latitude',
                    'lng'  => 'longitude',
                ],
            ];

            foreach ($sheets as $sheetName) {

                $range = $sheetName . '!A1:Z';
                $response = $service->spreadsheets_values->get($this->spreadsheetId, $range);
                $values = $response->getValues();

                if (empty($values)) continue;

                $header = array_map(function($h){
                    return strtolower(str_replace('_',' ', trim($h)));
                }, $values[0]);

                $config = $sheetConfig[$sheetName];

                $namaIndex              = array_search($config['nama'], $header);
                $latIndex               = array_search($config['lat'], $header);
                $lngIndex               = array_search($config['lng'], $header);
                $statusIndex            = array_search('stat', $header);
                $visitIndex             = array_search('visiting', $header);
                $internetIndex          = array_search('no internet', $header);
                $hasilIndex             = array_search('hasil', $header);
                $alamatIndex            = array_search('alamat', $header);
                $followUpIndex          = array_search('follow up', $header);
                $tglPsIndex             = array_search('tanggal ps', $header);
                $npsnIndex             = array_search('npsn', $header);
                $jenjangIndex          = array_search('jenjang', $header);
                $nipnasIndex           = array_search('nipnas', $header);

                if ($namaIndex === false || $latIndex === false || $lngIndex === false) {
                    continue;
                }

                foreach (array_slice($values,1) as $index => $row) {

                    $rowIndex = $index + 2;

                    $lat = $row[$latIndex] ?? null;
                    $lng = $row[$lngIndex] ?? null;

                    if(!$lat || !$lng) continue;

                    $status = trim($row[$statusIndex] ?? '');

                    if($status == ''){
                        $status = 'NOT_VISIT';
                    }

                    $item = [
                        'id' => $rowIndex,
                        'sheet' => $sheetName,
                        'nama' => $row[$namaIndex] ?? '',
                        'lat' => (float)$lat,
                        'lng' => (float)$lng,
                        'status' => strtoupper($status),
                        'visit' => $row[$visitIndex] ?? '-',
                        'nomor_internet' => $row[$internetIndex] ?? '-',
                        'hasil' => $row[$hasilIndex] ?? '-',
                        'alamat' => $row[$alamatIndex] ?? '-',
                        'follow_up'   => $row[$followUpIndex] ?? '-',
                        'npsn'         => $row[$npsnIndex]    ?? '-';
                        'jenjang'  => $row[$jenjangIndex] ?? '-';
                        'nipnas'  => $row[$nipnasIndex]  ?? '-';
                        'tanggal_ps'  => $tglPsIndex !== false ? ($row[$tglPsIndex] ?? '') : ''
                    ];

                    switch($sheetName){

                        case 'New_Education':
                            $data['education'][] = $item;
                            break;

                        case 'New_SPPG':
                            $data['sppg'][] = $item;
                            break;

                        case 'New_KDMP':
                            $data['kdmp'][] = $item;
                            break;

                        case 'New_Faskes':
                            $data['faskes'][] = $item;
                            break;

                        case 'New_Hotel':
                            $data['hotel'][] = $item;
                            break;

                        case 'New_Bank':
                            $data['bank'][] = $item;
                            break;
                        case 'New_Wisata':
                            $data['wisata'][] = $item;
                            break;
                        case 'New_Koperasi':
                            $data['koperasi'][] = $item;
                            break;
                    }

                }

            }

            return $data;

        });

        return view('map',[
            'education'=>$data['education'],
            'sppg'=>$data['sppg'],
            'kdmp'=>$data['kdmp'],
            'faskes'=>$data['faskes'],
            'koperasi'=>$data['koperasi'],
            'wisata'=>$data['wisata'],
            'bank'=>$data['bank'],
            'hotel'=>$data['hotel']
        ]);

    }


    public function edit($id)
    {
        $data = Cache::get('battle_map_cache');

        if (!$data) {
            return redirect('/')->with('error','Data belum tersedia, buka map dulu.');
        }

        $all = collect($data['education'])
            ->merge($data['sppg'])
            ->merge($data['kdmp'])
            ->merge($data['faskes'])
            ->merge($data['hotel'])
            ->merge($data['bank'])
            ->merge($data['wisata'])
            ->merge($data['koperasi']);

        $location = $all->firstWhere('id', $id);

        if (!$location) {
            abort(404, 'Location not found');
        }

        return view('edit-location', compact('location'));
    }

    public function update(Request $request, $id)
    {
        $sheet = $request->sheet;

        $client = new \Google_Client();
        $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
        $client->setAuthConfig($credentials);
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS);

        $service = new \Google_Service_Sheets($client);

        // =========================================================
        // Ambil header sheet target
        // =========================================================
        $headerRange    = $sheet . '!1:1';
        $headerResponse = $service->spreadsheets_values->get($this->spreadsheetId, $headerRange);
        $headers        = array_map(
            fn($h) => strtolower(trim($h)),
            $headerResponse->getValues()[0] ?? []
        );

        // =========================================================
        // Baca nilai LAMA dari baris yang akan di-update
        // =========================================================
        $rowRange    = $sheet . '!' . $id . ':' . $id;
        $rowResponse = $service->spreadsheets_values->get($this->spreadsheetId, $rowRange);
        $oldRow      = $rowResponse->getValues()[0] ?? [];

        // Helper: ambil nilai lama berdasarkan nama kolom
        $getOld = function (string $colName) use ($headers, $oldRow): string {
            $idx = array_search($colName, $headers);
            return ($idx !== false && isset($oldRow[$idx])) ? trim($oldRow[$idx]) : '';
        };

        // =========================================================
        // Nama lokasi (untuk history label)
        // =========================================================
        // Coba beberapa kemungkinan header nama
        $namaCol  = 'nama satuan pendidikan';
        foreach (['nama satuan pendidikan','nama sppg','nama kdmp','nama faskes','nama hotel','nama wisata','nama koperasi','nama bank'] as $nc) {
            if (array_search($nc, $headers) !== false) { $namaCol = $nc; break; }
        }
        $namaLokasi = $getOld($namaCol) ?: ('Row #' . $id);

        // =========================================================
        // Map field → nilai baru & nama kolom sheet
        // =========================================================
        $colMap = [
            'stat'        => $request->status,
            'visiting'    => $request->visit,
            'no internet' => $request->nomor_internet,
            'hasil'       => $request->hasil,
            'follow up'   => $request->follow_up,
            'tanggal ps'  => $request->tanggal_ps,
        ];

        // Label ramah untuk history
        $fieldLabels = [
            'stat'        => 'Status',
            'visiting'    => 'Visiting',
            'no internet' => 'No Internet',
            'hasil'       => 'Hasil',
            'follow up'   => 'Follow Up',
            'tanggal ps'  => 'Tanggal PS',
        ];

        // =========================================================
        // Kumpulkan perubahan untuk history
        // =========================================================
        $historyRows = [];
        $timestamp   = now()->format('d/m/Y H:i:s');
        $user = session('auth_user')['name'] ?? '—';

        foreach ($colMap as $colName => $newVal) {
            $oldVal = $getOld($colName);
            $newVal = $newVal ?? '';

            // Hanya catat jika ada perubahan
            if (trim($oldVal) !== trim($newVal)) {
                $historyRows[] = [
                    $timestamp,
                    $sheet,
                    (string) $id,
                    $namaLokasi,
                    $fieldLabels[$colName] ?? $colName,
                    $oldVal,
                    $newVal,
                    $user,
                ];
            }
        }

        // =========================================================
        // Update nilai ke sheet target
        // =========================================================
        $data = [];
        foreach ($colMap as $colName => $value) {
            $colIndex = array_search($colName, $headers);
            if ($colIndex === false) continue;

            $n         = $colIndex + 1;
            $colLetter = '';
            while ($n > 0) {
                $mod       = ($n - 1) % 26;
                $colLetter = chr(65 + $mod) . $colLetter;
                $n         = (int)(($n - $mod) / 26);
            }

            $data[] = new \Google_Service_Sheets_ValueRange([
                'range'  => $sheet . '!' . $colLetter . $id,
                'values' => [[$value ?? '']],
            ]);
        }

        if (!empty($data)) {
            $body = new \Google_Service_Sheets_BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data'             => $data,
            ]);
            $service->spreadsheets_values->batchUpdate($this->spreadsheetId, $body);
        }

        // =========================================================
        // Tulis history ke sheet "History"
        // =========================================================
        if (!empty($historyRows)) {
            $this->appendHistory($service, $historyRows);
        }

        Cache::forget('battle_map_cache');
        Cache::forget('analytics_raw');

        return response()->json(['status' => 'success']);
    }

    // =========================================================
    // Helper: append baris ke sheet History
    // =========================================================
    private function appendHistory(\Google_Service_Sheets $service, array $rows): void
    {
        $body = new \Google_Service_Sheets_ValueRange([
            'values' => $rows,
        ]);

        $service->spreadsheets_values->append(
            $this->spreadsheetId,
            'History!A:H',
            $body,
            ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
        );
    }
}
