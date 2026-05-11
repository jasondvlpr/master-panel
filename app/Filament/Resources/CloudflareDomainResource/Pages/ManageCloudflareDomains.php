<?php

namespace App\Filament\Resources\CloudflareDomainResource\Pages;

use App\Filament\Resources\CloudflareDomainResource;
use App\Models\CloudflareAccount;
use App\Models\CloudflareDomain;
use App\Services\CloudflareService;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Notifications\Notification;

class ManageCloudflareDomains extends ManageRecords
{
    protected static string $resource = CloudflareDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_all')
                ->label('Sync All Accounts')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $accounts = CloudflareAccount::all();
                    $totalSynced = 0;

                    foreach ($accounts as $account) {
                        $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);
                        $response = $cf->getZones();

                        if (isset($response['success']) && $response['success']) {
                            foreach ($response['result'] as $zone) {
                                CloudflareDomain::updateOrCreate(
                                    ['zone_id' => $zone['id']],
                                    [
                                        'cloudflare_account_id' => $account->id,
                                        'name' => $zone['name'],
                                        'status' => $zone['status'],
                                        'name_servers' => $zone['name_servers'] ?? [],
                                        'paused' => $zone['paused'] ?? false,
                                        'last_synced_at' => now(),
                                    ]
                                );
                                $totalSynced++;
                            }
                        }
                    }

                    Notification::make()
                        ->title('Global Sync Completed')
                        ->success()
                        ->body($totalSynced . ' domains have been synced across all accounts.')
                        ->send();
                }),

            Actions\CreateAction::make()
                ->label('Add Domain')
                ->icon('heroicon-o-plus')
                ->modalWidth('md')
                ->using(function (array $data): CloudflareDomain {
                    $account = CloudflareAccount::find($data['cloudflare_account_id']);
                    $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);
                    
                    $result = $cf->addZone($data['name']);

                    if (isset($result['success']) && $result['success']) {
                        $zone = $result['result'];
                        return CloudflareDomain::create([
                            'zone_id' => $zone['id'],
                            'cloudflare_account_id' => $account->id,
                            'name' => $zone['name'],
                            'status' => $zone['status'],
                            'name_servers' => $zone['name_servers'] ?? [],
                            'paused' => $zone['paused'] ?? false,
                            'last_synced_at' => now(),
                        ]);
                    }

                    $error = $result['errors'][0]['message'] ?? 'Cloudflare API Error';
                    Notification::make()->title('Failed to Add Domain')->danger()->body($error)->send();
                    
                    throw new \Exception($error);
                }),
        ];
    }
}
