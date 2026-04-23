<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetsService
{
    protected $service;

    public function __construct()
    {
        $client = new Client();
        
        // Baca dari environment variable, bukan dari file
        $credentials = json_decode(env('GOOGLE_CREDENTIALS'), true);
        $client->setAuthConfig($credentials);
        
        $client->addScope(Sheets::SPREADSHEETS_READONLY);
        $this->service = new Sheets($client);
    }

    public function getData(string $sheetName)
    {
        $spreadsheetId = env('GOOGLE_SHEETS_ID');
        $range = $sheetName . '!A:Z';

        $response = $this->service->spreadsheets_values->get(
            $spreadsheetId,
            $range
        );

        return $response->getValues();
    }
}