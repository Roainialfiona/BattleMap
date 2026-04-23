<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    private $spreadsheetId = '1tRA1dcU208Fdw3BGHnaLB6jCtBGRHJEIiOH7GmFkoP8';

    // =========================================================
    // Middleware manual — hanya admin
    // =========================================================
    private function requireAdmin()
    {
        if (!AuthController::isAdmin()) abort(403, 'Akses ditolak.');
    }

    // =========================================================
    // Ambil semua user + nomor baris di sheet
    // =========================================================
    private function getUserRows(): array
    {
        $service = $this->sheetsService();
        $values  = $service->spreadsheets_values
            ->get($this->spreadsheetId, 'Users!A:D')
            ->getValues() ?? [];

        $rows = [];
        foreach (array_slice($values, 1) as $i => $row) {
            $username = trim($row[0] ?? '');
            if (!$username) continue;
            $rows[] = [
                'row_number' => $i + 2, // +2 karena header di baris 1
                'username'   => $username,
                'password'   => $row[1] ?? '',
                'name'       => $row[2] ?? $username,
                'role'       => strtolower($row[3] ?? 'user'),
            ];
        }

        return $rows;
    }

    // =========================================================
    // Halaman manage user
    // =========================================================
    public function index()
    {
        $this->requireAdmin();
        $users = $this->getUserRows();
        return view('users.index', compact('users'));
    }

    // =========================================================
    // Tambah user baru (append baris baru ke sheet)
    // =========================================================
    public function store(Request $request)
    {
        $this->requireAdmin();

        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|max:50',
            'password' => 'required|string|min:4',
            'role'     => 'required|in:admin,user',
        ]);

        // Cek username sudah ada
        foreach ($this->getUserRows() as $u) {
            if (strtolower($u['username']) === strtolower($request->username)) {
                return back()->with('error', 'Username sudah digunakan.');
            }
        }

        $service = $this->sheetsService(false);
        $body    = new \Google_Service_Sheets_ValueRange([
            'values' => [[
                $request->username,
                $request->password,
                $request->name,
                $request->role,
            ]],
        ]);

        $service->spreadsheets_values->append(
            $this->spreadsheetId,
            'Users!A:D',
            $body,
            ['valueInputOption' => 'RAW', 'insertDataOption' => 'INSERT_ROWS']
        );

        return back()->with('success', 'User berhasil ditambahkan.');
    }

    // =========================================================
    // Hapus user (hapus baris dari sheet berdasarkan row_number)
    // =========================================================
    public function destroy(Request $request, $username)
    {
        $this->requireAdmin();

        $authUser = AuthController::authUser();
        if ($authUser && strtolower($authUser['username']) === strtolower($username)) {
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        $users = $this->getUserRows();
        $target = collect($users)->firstWhere('username', $username);

        if (!$target) return back()->with('error', 'User tidak ditemukan.');

        // Hapus baris dengan clearValues lalu shift — cara termudah: clear isi baris
        // Google Sheets tidak punya "delete row" via values API, pakai batchUpdate
        $service = $this->sheetsService(false);

        $sheetId = $this->getSheetId($service, 'Users');

        $body = new \Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
            'requests' => [
                new \Google_Service_Sheets_Request([
                    'deleteDimension' => [
                        'range' => [
                            'sheetId'    => $sheetId,
                            'dimension'  => 'ROWS',
                            'startIndex' => $target['row_number'] - 1, // 0-based
                            'endIndex'   => $target['row_number'],
                        ],
                    ],
                ]),
            ],
        ]);

        $service->spreadsheets->batchUpdate($this->spreadsheetId, $body);

        return back()->with('success', 'User berhasil dihapus.');
    }

    // =========================================================
    // Reset password — update kolom B di baris yang sesuai
    // =========================================================
    public function resetPassword(Request $request, $username)
    {
        $this->requireAdmin();

        $request->validate(['password' => 'required|string|min:4']);

        $users  = $this->getUserRows();
        $target = collect($users)->firstWhere('username', $username);

        if (!$target) return back()->with('error', 'User tidak ditemukan.');

        $service = $this->sheetsService(false);
        $range   = 'Users!B' . $target['row_number'];

        $body = new \Google_Service_Sheets_ValueRange([
            'range'  => $range,
            'values' => [[$request->password]],
        ]);

        $service->spreadsheets_values->update(
            $this->spreadsheetId,
            $range,
            $body,
            ['valueInputOption' => 'RAW']
        );

        return back()->with('success', 'Password berhasil direset.');
    }

    // =========================================================
    // Helper: buat Google Sheets service
    // =========================================================
    private function sheetsService(bool $readOnly = true): \Google_Service_Sheets
    {
        $client = new \Google_Client();
        $client->setApplicationName('Battle Map');
        $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
        $client->setAuthConfig($credentials);
        $client->addScope($readOnly
            ? \Google_Service_Sheets::SPREADSHEETS_READONLY
            : \Google_Service_Sheets::SPREADSHEETS
        );
        return new \Google_Service_Sheets($client);
    }

    // =========================================================
    // Helper: ambil sheetId dari nama sheet
    // =========================================================
    private function getSheetId(\Google_Service_Sheets $service, string $sheetName): int
    {
        $meta = $service->spreadsheets->get($this->spreadsheetId);
        foreach ($meta->getSheets() as $sheet) {
            if ($sheet->getProperties()->getTitle() === $sheetName) {
                return $sheet->getProperties()->getSheetId();
            }
        }
        abort(500, "Sheet '$sheetName' tidak ditemukan.");
    }
}
