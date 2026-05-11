<?php

namespace App\Filament\Resources\Tenants\Pages;

use App\Filament\Resources\Tenants\TenantResource;
use App\Models\Server;
use App\Models\Tenant;
use App\Services\TenantService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Forms;
use Filament\Notifications\Notification;

class ManageTenants extends ManageRecords
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Sync disandingkan dengan Create
            Action::make('sync_tenants')
                ->label('Sync Tenants')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalWidth('sm')
                ->form([
                    Forms\Components\Select::make('server_id')
                        ->label('Select Server to Sync')
                        ->options(Server::pluck('name', 'id'))
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $server = Server::find($data['server_id']);
                    if (!$server) return;

                    $service = new TenantService($server->api_endpoint, $server->api_key);
                    $response = $service->getTenants();

                    if (isset($response['status']) && $response['status'] === 'success') {
                        $count = 0;
                        foreach ($response['data'] as $remoteTenant) {
                            $remoteId = $remoteTenant['tenant_id'] ?? $remoteTenant['id'] ?? null;
                            if (!$remoteId) continue;
                            
                            $tenant = Tenant::updateOrCreate(
                                ['remote_id' => (string) $remoteId, 'server_id' => $server->id],
                                [
                                    'name' => $remoteTenant['tenant_name'] ?? $remoteTenant['name'] ?? 'Unnamed Site',
                                    'domain' => $remoteTenant['domain'] ?? 'unknown',
                                    'status' => 'active',
                                    'data' => $remoteTenant,
                                ]
                            );

                            // Sync Primary Domain
                            if ($tenant->domain && $tenant->domain !== 'unknown') {
                                $tenant->domains()->updateOrCreate(
                                    ['domain' => $tenant->domain],
                                    ['type' => 'primary']
                                );
                            }

                            // Sync Aliases
                            if (isset($remoteTenant['aliases']) && is_array($remoteTenant['aliases'])) {
                                foreach ($remoteTenant['aliases'] as $alias) {
                                    $tenant->domains()->updateOrCreate(
                                        ['domain' => $alias],
                                        ['type' => 'alias']
                                    );
                                }
                                
                                // Cleanup old aliases not in remote
                                $tenant->domains()
                                    ->where('type', 'alias')
                                    ->whereNotIn('domain', $remoteTenant['aliases'])
                                    ->delete();
                            }

                            $count++;
                        }

                        Notification::make()
                            ->title('Sync Completed')
                            ->success()
                            ->body($count . ' tenants synced from ' . $server->name)
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Sync Failed')
                            ->danger()
                            ->body($response['message'] ?? 'Check your Server API settings.')
                            ->send();
                    }
                }),

            CreateAction::make()
                ->label('New Tenant')
                ->modalWidth('xl')
                ->using(function (array $data, string $model): Tenant {
                    $server = Server::find($data['server_id']);
                    
                    if (!$server) {
                        throw new \Exception('Server not found.');
                    }

                    // 1. Panggil API Remote
                    $service = new TenantService($server->api_endpoint, $server->api_key);
                    $response = $service->createTenant([
                        'name' => $data['name'],
                        'domain' => $data['domain'],
                        'admin_username' => $data['admin_username'],
                        'admin_password' => $data['admin_password'],
                    ]);

                    if (isset($response['status']) && $response['status'] === 'success') {
                        // 2. Simpan ke Database Lokal dengan remote_id dari API
                        $tenant = $model::create([
                            'name' => $data['name'],
                            'domain' => $data['domain'],
                            'server_id' => $data['server_id'],
                            'status' => $data['status'] ?? 'active',
                            'remote_id' => $response['data']['tenant_id'] ?? null, // Ambil tenant_id dari respon API
                            'data' => $response['data'] ?? [],
                        ]);

                        // Simpan domain utama ke tabel domains
                        $tenant->domains()->create([
                            'domain' => $tenant->domain,
                            'type' => 'primary',
                        ]);

                        return $tenant;
                    }

                    // Jika API Gagal
                    $message = $response['message'] ?? 'Failed to create tenant on remote server.';
                    Notification::make()->title('API Error')->danger()->body($message)->send();
                    
                    throw new \Exception($message);
                }),
        ];
    }
}
