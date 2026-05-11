<?php

namespace App\Jobs;

use App\Models\ManagedDomain;
use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Services\CloudflareService;
use App\Services\TenantService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class ProcessDomainWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ManagedDomain $managedDomain)
    {
    }

    public function handle(): void
    {
        try {
            $domain = $this->managedDomain;
            $server = $domain->server;
            
            $cf = new CloudflareService(
                $domain->cloudflareAccount->api_token,
                $domain->cloudflareAccount->email,
                $domain->cloudflareAccount->api_key
            );

            // Ambil Zone ID yang sudah ada atau daftar baru
            $zoneId = $domain->cloudflare_zone_id;

            // 1. Register Zone to Cloudflare
            if (!$zoneId) {
                $this->logStep('register_zone', 'Registering domain to Cloudflare...');
                $domain->update(['status' => 'register_zone']);
                
                $zoneResult = $cf->addZone($domain->domain_name);
                
                if (isset($zoneResult['success']) && !$zoneResult['success']) {
                    if (($zoneResult['errors'][0]['code'] ?? 0) === 1061) {
                        $this->logStep('register_zone', 'Domain already exists in Cloudflare, fetching ID...');
                        $zones = $cf->getZones();
                        if (isset($zones['success']) && $zones['success']) {
                            foreach ($zones['result'] as $z) {
                                if ($z['name'] === $domain->domain_name) {
                                    $zoneId = $z['id'];
                                    $domain->update(['cloudflare_zone_id' => $zoneId]);
                                    break;
                                }
                            }
                        }
                    }
                    if (!$zoneId) {
                        throw new Exception("Cloudflare Zone ID error: " . ($zoneResult['errors'][0]['message'] ?? 'Unknown error'));
                    }
                } else {
                    $zoneId = $zoneResult['result']['id'];
                    $domain->update(['cloudflare_zone_id' => $zoneId]);
                    $this->logStep('register_zone', 'Registered to Cloudflare', 'success');
                }
            }

            // 2. Configure DNS in Cloudflare
            $this->logStep('configuring_dns', 'Configuring DNS records...');
            $domain->update(['status' => 'configuring_dns']);
            
            $pureIp = $server->ip;
            if (preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $pureIp, $matches)) {
                $pureIp = $matches[0];
            }

            $dnsResult = $cf->addDnsRecord(
                $zoneId,
                'A',
                $domain->subdomain ?? '@',
                $pureIp
            );

            if (isset($dnsResult['success']) && !$dnsResult['success']) {
                if (($dnsResult['errors'][0]['code'] ?? 0) !== 81057) {
                    throw new Exception("Cloudflare DNS Error: " . ($dnsResult['errors'][0]['message'] ?? 'Unknown error'));
                }
            }
            $this->logStep('configuring_dns', 'DNS configured successfully', 'success');

            // 3. Create Tenant via API
            $this->logStep('creating_tenant', 'Creating tenant on remote server...');
            $domain->update(['status' => 'creating_tenant']);
            
            $fullDomain = $domain->subdomain ? "{$domain->subdomain}.{$domain->domain_name}" : $domain->domain_name;
            
            $tenantService = new TenantService($server->api_endpoint, $server->api_key);
            $tenantResult = $tenantService->createTenant([
                'name' => $domain->domain_name,
                'domain' => $fullDomain,
                'admin_username' => 'admin', // Default
                'admin_password' => 'password123', // Default
            ]);

            if (isset($tenantResult['status']) && $tenantResult['status'] === 'success') {
                // Simpan ke tabel Tenants lokal
                Tenant::updateOrCreate(
                    ['remote_id' => $tenantResult['data']['tenant_id'], 'server_id' => $server->id],
                    [
                        'name' => $tenantResult['data']['name'],
                        'domain' => $tenantResult['data']['domain'],
                        'managed_domain_id' => $domain->id,
                        'status' => 'active',
                        'data' => $tenantResult['data'],
                    ]
                );
                $this->logStep('creating_tenant', 'Tenant created and linked successfully', 'success');
            } else {
                throw new Exception("Tenant API Error: " . ($tenantResult['message'] ?? 'Unknown error'));
            }

            // Completed
            $domain->update(['status' => 'completed']);
            $this->logStep('completed', 'Workflow completed successfully', 'success');

        } catch (Exception $e) {
            $this->managedDomain->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
            ]);
            $this->logStep('failed', $e->getMessage(), 'error');
        }
    }

    protected function logStep($step, $message, $status = 'processing')
    {
        ActivityLog::create([
            'managed_domain_id' => $this->managedDomain->id,
            'step' => $step,
            'status' => $status,
            'message' => $message,
        ]);
    }
}
