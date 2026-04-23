<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        // ================= PERIODE FILTER =================
        $periode = $request->get('periode', 'all');
        $from    = null;
        $to      = null;

        switch ($periode) {
            case 'week':
                $from = Carbon::now()->startOfWeek()->format('Y-m-d');
                $to   = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $from = Carbon::now()->startOfMonth()->format('Y-m-d');
                $to   = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'custom':
                $from = $request->get('from');
                $to   = $request->get('to');
                break;
            default:
                $periode = 'all';
        }

        // Raw data di-cache 60 menit, filtering dilakukan setelah cache
        // Clear cache kalau ada perubahan filter tanggal
        if ($periode !== 'all') Cache::forget('analytics_raw');
        $raw = Cache::remember('analytics_raw', 60, function () {

            $client = new \Google_Client();
            $client->setApplicationName('Battle Map');
            $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
            $client->setAuthConfig($credentials);
            $client->addScope(\Google_Service_Sheets::SPREADSHEETS);

            $service       = new \Google_Service_Sheets($client);
            $spreadsheetId = '1tRA1dcU208Fdw3BGHnaLB6jCtBGRHJEIiOH7GmFkoP8';
            $sheets        = ['New_Education', 'New_SPPG', 'New_KDMP', 'New_Faskes'];
            $katMap        = [
                'New_Education' => 'Education',
                'New_SPPG'      => 'SPPG',
                'New_KDMP'      => 'KDMP',
                'New_Faskes'    => 'Faskes',
            ];

            $rows = [];

            foreach ($sheets as $sheetName) {
                $range  = "'" . $sheetName . "'!A1:Z";
                $values = $service->spreadsheets_values->get($spreadsheetId, $range)->getValues();

                if (empty($values)) continue;

                $header    = array_map(fn($h) => strtolower(trim($h)), $values[0]);
                $latIndex  = array_search('latitude',  $header);
                $lngIndex  = array_search('longitude', $header);
                $statIndex = array_search('stat',      $header);
                $wilIndex  = array_search('kecamatan', $header);
                $dateIndex = array_search('tanggal ps', $header);
    
                foreach (array_slice($values, 1) as $row) {
                    $lat = $row[$latIndex] ?? null;
                    $lng = $row[$lngIndex] ?? null;
                    if (!$lat || !$lng) continue;

                    $stat    = strtolower(trim($row[$statIndex] ?? 'unknown'));
                    $wil     = $wilIndex !== false
                        ? ucwords(strtolower(trim($row[$wilIndex] ?? 'Unknown')))
                        : 'Unknown';
                    $dateRaw = $dateIndex !== false ? ($row[$dateIndex] ?? null) : null;
                    $date    = null;
                    if ($dateRaw) {
                        // Handle format DD/MM/YYYY dari Google Sheets
                        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($dateRaw), $m)) {
                            $date = $m[3] . '-' . $m[2] . '-' . $m[1]; // → YYYY-MM-DD
                        } else {
                            $parsed = strtotime($dateRaw);
                            if ($parsed) $date = date('Y-m-d', $parsed);
                        }
                    }

                    $rows[] = [
                        'stat'     => $stat,
                        'wilayah'  => $wil,
                        'date'     => $date,
                        'kategori' => $katMap[$sheetName] ?? $sheetName,
                    ];
                }
            }

            return $rows;
        });

        // ================= APPLY PERIODE FILTER =================
        $filtered = collect($raw)->filter(function ($row) use ($periode, $from, $to) {
            if ($periode === 'all') return true;
            if (!$row['date'])     return false;
            $d = Carbon::parse($row['date']);
            return $d->between(Carbon::parse($from), Carbon::parse($to));
        })->values();

        // ================= HITUNG STATS =================
        $statusCounts   = ['win' => 0, 'lose' => 0, 'unknown' => 0];
        $wilayah        = [];
        $daily          = [];
        $loseWilayah    = [];
        $points         = [];
        $kategoriDetail = [
            'Education' => ['total' => 0, 'win' => 0, 'lose' => 0, 'unknown' => 0],
            'SPPG'      => ['total' => 0, 'win' => 0, 'lose' => 0, 'unknown' => 0],
            'KDMP'      => ['total' => 0, 'win' => 0, 'lose' => 0, 'unknown' => 0],
            'Faskes'    => ['total' => 0, 'win' => 0, 'lose' => 0, 'unknown' => 0],
        ];

        foreach ($filtered as $row) {
            $stat = $row['stat'];
            $wil  = $row['wilayah'];
            $date = $row['date'];
            $kat  = $row['kategori'];

            if ($stat === 'win')      $statusCounts['win']++;
            elseif ($stat === 'lose') $statusCounts['lose']++;
            else                      $statusCounts['unknown']++;

            $wilayah[$wil] = ($wilayah[$wil] ?? 0) + 1;
            if ($stat === 'lose') $loseWilayah[$wil] = ($loseWilayah[$wil] ?? 0) + 1;

            $date = $row['date'];
            if ($date) {
                if (!isset($daily[$date])) $daily[$date] = ['win' => 0, 'lose' => 0, 'unknown' => 0];
                if ($stat === 'win')      $daily[$date]['win']++;
                elseif ($stat === 'lose') $daily[$date]['lose']++;
                else                      $daily[$date]['unknown']++;
            }

            $points[] = ['wilayah' => $wil, 'status' => $stat];

            if (isset($kategoriDetail[$kat])) {
                $kategoriDetail[$kat]['total']++;
                if ($stat === 'win')      $kategoriDetail[$kat]['win']++;
                elseif ($stat === 'lose') $kategoriDetail[$kat]['lose']++;
                else                      $kategoriDetail[$kat]['unknown']++;
            }
        }

        foreach ($kategoriDetail as $k => $v) {
            $t = $v['total'];
            $kategoriDetail[$k]['win_pct']     = $t ? ($v['win']     / $t) * 100 : 0;
            $kategoriDetail[$k]['lose_pct']    = $t ? ($v['lose']    / $t) * 100 : 0;
            $kategoriDetail[$k]['unknown_pct'] = $t ? ($v['unknown'] / $t) * 100 : 0;
        }

        $totalAll       = array_sum($statusCounts);
        $played         = $statusCounts['win'] + $statusCounts['lose'];
        $conversionRate = $played > 0 ? ($statusCounts['win'] / $played) * 100 : 0;
        $notVisit       = $statusCounts['unknown'];

        $group = [];
        foreach ($points as $p) {
            $wil = $p['wilayah'];
            if (!isset($group[$wil])) {
                $group[$wil] = ['total' => 0, 'win' => 0, 'lose' => 0, 'unknown' => 0, 'played' => 0];
            }
            $group[$wil]['total']++;
            if ($p['status'] === 'win')      { $group[$wil]['win']++;  $group[$wil]['played']++; }
            elseif ($p['status'] === 'lose') { $group[$wil]['lose']++; $group[$wil]['played']++; }
            else                               $group[$wil]['unknown']++;
        }

        foreach ($group as $w => $v) {
            $t = $v['total'];
            $group[$w]['win_pct']     = $t ? ($v['win']     / $t) * 100 : 0;
            $group[$w]['lose_pct']    = $t ? ($v['lose']    / $t) * 100 : 0;
            $group[$w]['unknown_pct'] = $t ? ($v['unknown'] / $t) * 100 : 0;
            $group[$w]['conversion']  = $v['played'] ? ($v['win'] / $v['played']) * 100 : 0;
        }

        $topWilayah = collect($group)->sortByDesc('total')->take(5);
        $topLose    = collect($loseWilayah)->sortDesc()->take(3);

        return view('analytics', [
            'kategoriDetail' => $kategoriDetail,
            'win'            => $statusCounts['win'],
            'lose'           => $statusCounts['lose'],
            'unknown'        => $statusCounts['unknown'],
            'totalAll'       => $totalAll,
            'conversionRate' => round($conversionRate, 2),
            'notVisit'       => $notVisit,
            'topWilayah'     => $topWilayah,
            'topLose'        => $topLose,
            'wilayah'        => $wilayah,
            'daily'          => $daily,
            'winRate'        => round($conversionRate, 2),
            'periode'        => $periode,
            'from'           => $from,
            'to'             => $to,
        ]);
    }
}