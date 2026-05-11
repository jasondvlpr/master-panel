<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TenantService
{
    protected $baseUrl;
    protected $apiKey;

    public function __construct(string $baseUrl, string $apiKey)
    {
        // Normalisasi URL: Jika sudah ada /tenants, kita gunakan sebagai base utama untuk List/Create
        // Tapi untuk Delete/Alias kita butuh base-nya saja.
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
    }

    protected function getRequest()
    {
        return Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    public function getTenants()
    {
        // Jika baseUrl sudah berisi /tenants, jangan tambahkan lagi
        $url = str_contains($this->baseUrl, '/tenants') ? $this->baseUrl : "{$this->baseUrl}/tenants";
        $response = $this->getRequest()->get($url);
        return $response->json();
    }

    public function createTenant(array $data)
    {
        $url = str_contains($this->baseUrl, '/tenants') ? $this->baseUrl : "{$this->baseUrl}/tenants";
        $response = $this->getRequest()->post($url, [
            'name' => $data['name'],
            'domain' => $data['domain'],
            'admin_username' => $data['admin_username'] ?? 'admin',
            'admin_password' => $data['admin_password'] ?? 'password123',
        ]);

        return $response->json();
    }

    public function deleteTenant($id)
    {
        // Pastikan endpoint delete benar: .../tenants/{id}
        $base = str_replace('/tenants', '', $this->baseUrl);
        $url = "{$base}/tenants/{$id}";
        
        $response = $this->getRequest()->delete($url);
        return $response->json();
    }

    public function addDomainAlias($tenantId, string $domain)
    {
        // Pastikan endpoint alias benar: .../tenants/{id}/domains
        $base = str_replace('/tenants', '', $this->baseUrl);
        $url = "{$base}/tenants/{$tenantId}/domains";
        
        $response = $this->getRequest()->post($url, [
            'domain' => $domain,
        ]);

        return $response->json();
    }
}
