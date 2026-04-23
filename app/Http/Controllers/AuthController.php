<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    private $spreadsheetId = '1tRA1dcU208Fdw3BGHnaLB6jCtBGRHJEIiOH7GmFkoP8';

    // =========================================================
    // Ambil semua user dari sheet "Users"
    // =========================================================
    private function getUsers(): array
    {
        $client = new \Google_Client();
        $client->setApplicationName('Battle Map');
        $client->setAuthConfig(storage_path('app/credentials.json'));
        $client->addScope(\Google_Service_Sheets::SPREADSHEETS_READONLY);

        $service  = new \Google_Service_Sheets($client);
        $response = $service->spreadsheets_values->get($this->spreadsheetId, 'Users!A:D');
        $values   = $response->getValues() ?? [];

        if (empty($values)) return [];

        $users = [];
        foreach (array_slice($values, 1) as $row) {
            $username = trim($row[0] ?? '');
            if (!$username) continue;
            $users[] = [
                'username' => $username,
                'password' => trim($row[1] ?? ''),
                'name'     => trim($row[2] ?? $username),
                'role'     => strtolower(trim($row[3] ?? 'user')),
            ];
        }

        return $users;
    }

    private function findUser(string $username): ?array
    {
        foreach ($this->getUsers() as $user) {
            if (strtolower($user['username']) === strtolower($username)) {
                return $user;
            }
        }
        return null;
    }

    // =========================================================
    // Helper global — cek apakah sudah login dari session
    // =========================================================
    public static function authUser(): ?array
    {
        return session('auth_user');
    }

    public static function isAdmin(): bool
    {
        $user = session('auth_user');
        return $user && $user['role'] === 'admin';
    }

    // =========================================================
    // Halaman login
    // =========================================================
    public function showLogin()
    {
        if (session('auth_user')) return redirect('/dashboard');
        return view('Auth.login');
    }

    // =========================================================
    // Proses login
    // =========================================================
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $user = $this->findUser($request->username);

        if (!$user || $user['password'] !== $request->password) {
            return back()->withErrors([
                'username' => 'Username atau password salah.',
            ])->withInput($request->only('username'));
        }

        $request->session()->put('auth_user', [
            'username' => $user['username'],
            'name'     => $user['name'],
            'role'     => $user['role'],
        ]);

        return redirect()->intended('/dashboard');
    }

    // =========================================================
    // Logout
    // =========================================================
    public function logout(Request $request)
    {
        $request->session()->forget('auth_user');
        return redirect('/login');
    }
}
