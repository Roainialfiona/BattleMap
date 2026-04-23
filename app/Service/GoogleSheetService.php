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
        $client->setAuthConfig(storage_path('app/credentials.json'));
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