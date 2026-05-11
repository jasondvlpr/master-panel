<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AaPanelService
{
    protected $ip;
    protected $apiKey;

    public function __construct(string $ip, string $apiKey)
    {
        $this->ip = $ip;
        $this->apiKey = $apiKey;
    }

    protected function getUrl(string $path)
    {
        // Jika input sudah diawali dengan http/https, gunakan langsung
        if (str_starts_with($this->ip, 'http')) {
            $base = rtrim($this->ip, '/');
            return "{$base}/{$path}";
        }

        // Default fallback ke port 8888
        return "http://{$this->ip}:8888/{$path}";
    }

    protected function getToken()
    {
        $time = time();
        return [
            'request_time' => $time,
            'request_token' => md5($time . md5($this->apiKey)),
        ];
    }

    public function addDomainAlias(string $siteName, string $domain)
    {
        $token = $this->getToken();
        $response = Http::asForm()->post($this->getUrl('site?action=AddDomain'), array_merge($token, [
            'webname' => $siteName,
            'domain' => $domain,
        ]));

        return $response->json();
    }
}
