<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareService
{
    protected $baseUrl = 'https://api.cloudflare.com/client/v4';
    protected $token;
    protected $email;
    protected $apiKey;

    public function __construct($token = null, $email = null, $apiKey = null)
    {
        $this->token = $token;
        $this->email = $email;
        $this->apiKey = $apiKey;
    }

    protected function getRequest()
    {
        // Jika menggunakan Global API Key
        if ($this->email && $this->apiKey) {
            return Http::withHeaders([
                'X-Auth-Email' => $this->email,
                'X-Auth-Key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ]);
        }

        // Jika menggunakan API Token (Bearer)
        return Http::withToken($this->token);
    }

    public function getZones()
    {
        $allZones = [];
        $page = 1;
        $perPage = 50;

        do {
            $response = $this->getRequest()->get("{$this->baseUrl}/zones", [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $data = $response->json();

            if (isset($data['success']) && $data['success']) {
                $allZones = array_merge($allZones, $data['result']);
                $totalCount = $data['result_info']['total_count'] ?? 0;
                $page++;
            } else {
                return $data;
            }
        } while (count($allZones) < $totalCount && !empty($data['result']));

        return [
            'success' => true,
            'result' => $allZones,
        ];
    }

    public function addZone(string $name)
    {
        $response = $this->getRequest()->post("{$this->baseUrl}/zones", [
            'name' => $name,
            'jump_start' => true,
        ]);

        return $response->json();
    }

    public function addDnsRecord(string $zoneId, string $type, string $name, string $content, bool $proxied = true)
    {
        $response = $this->getRequest()->post("{$this->baseUrl}/zones/{$zoneId}/dns_records", [
            'type' => $type,
            'name' => $name,
            'content' => $content,
            'proxied' => $proxied,
            'ttl' => 1,
        ]);

        return $response->json();
    }

    public function getDnsRecords(string $zoneId)
    {
        $allRecords = [];
        $page = 1;
        $perPage = 100;

        do {
            $response = $this->getRequest()->get("{$this->baseUrl}/zones/{$zoneId}/dns_records", [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            $data = $response->json();

            if (isset($data['success']) && $data['success']) {
                $allRecords = array_merge($allRecords, $data['result']);
                $totalCount = $data['result_info']['total_count'] ?? 0;
                $page++;
            } else {
                return $data;
            }
        } while (count($allRecords) < $totalCount && !empty($data['result']));

        return [
            'success' => true,
            'result' => $allRecords,
        ];
    }

    public function deleteDnsRecord(string $zoneId, string $recordId)
    {
        $response = $this->getRequest()->delete("{$this->baseUrl}/zones/{$zoneId}/dns_records/{$recordId}");

        return $response->json();
    }

    public function getZoneByName(string $name)
    {
        $response = $this->getRequest()->get("{$this->baseUrl}/zones", [
            'name' => $name,
        ]);

        return $response->json();
    }

    public function getZoneDetails(string $zoneId)
    {
        $response = $this->getRequest()->get("{$this->baseUrl}/zones/{$zoneId}");

        return $response->json();
    }
}
