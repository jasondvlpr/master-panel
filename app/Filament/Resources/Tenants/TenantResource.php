<?php

namespace App\Filament\Resources\Tenants;

use App\Filament\Resources\Tenants\Pages;
use App\Models\Tenant;
use App\Models\Server;
use App\Services\TenantService;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Client Registry';
    protected static ?string $modelLabel = 'Tenant';
    protected static string | \UnitEnum | null $navigationGroup = 'Tenant Management';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Tenant Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Boss Group Client')
                    ->prefixIcon('heroicon-m-user-circle'),
                Forms\Components\Select::make('domain')
                    ->label('Domain')
                    ->required()
                    ->searchable()
                    ->options(function ($record) {
                        // Ambil semua domain yang sudah ada di tenant untuk pengecualian
                        $query = Tenant::query();
                        if ($record) {
                            $query->where('id', '!=', $record->id);
                        }
                        $existingDomains = $query->pluck('domain')->toArray();
                        
                        // Ambil domain dari CloudflareDomain yang belum ada di tenants (kecuali domain tenant ini sendiri)
                        return \App\Models\CloudflareDomain::whereNotIn('name', $existingDomains)
                            ->pluck('name', 'name');
                    })
                    ->placeholder('Select a cloudflare domain...')
                    ->prefixIcon('heroicon-m-globe-alt'),
                
                // Field Tambahan sesuai API Docs (Hanya muncul saat Create)
                Forms\Components\TextInput::make('admin_username')
                    ->label('Admin Username')
                    ->default('admin')
                    ->required()
                    ->hidden(fn ($record) => $record !== null), // Sembunyikan jika sedang Edit (ada record)
                
                Forms\Components\TextInput::make('admin_password')
                    ->label('Admin Password')
                    ->password()
                    ->default('password123')
                    ->required()
                    ->hidden(fn ($record) => $record !== null), // Sembunyikan jika sedang Edit (ada record)

                Forms\Components\Select::make('server_id')
                    ->label('Assigned Server')
                    ->relationship('server', 'name')
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-m-server'),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'pending' => 'Pending',
                    ])
                    ->required()
                    ->native(false)
                    ->prefixIcon('heroicon-m-check-circle'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Kolom Client: Avatar Adaptif & Teks Kontras
                Tables\Columns\TextColumn::make('name')
                    ->label('CLIENT & ID')
                    ->formatStateUsing(fn ($state, Tenant $record) => new HtmlString('
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="avatar-box" style="width: 42px; height: 42px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 800; color: #6366f1; font-size: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                '.strtoupper(substr($state, 0, 1)).'
                            </div>
                            <div style="display: flex; flex-direction: column;">
                                <div style="font-weight: 900; color: var(--tw-prose-headings, #0f172a); text-transform: uppercase; letter-spacing: -0.01em; font-size: 14px;">'.$state.'</div>
                                <div style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.12em; margin-top: 2px;">UID:'.$record->remote_id.'</div>
                            </div>
                        </div>
                        <style>
                            .dark .avatar-box { background: rgba(255,255,255,0.03) !important; border-color: rgba(255,255,255,0.1) !important; color: #818cf8 !important; }
                        </style>
                    '))
                    ->searchable(),

                // Kolom Endpoints: LEFT ALIGN, Custom UI
                Tables\Columns\TextColumn::make('domain')
                    ->label('ENDPOINTS')
                    ->searchable()
                    ->disabledClick()
                    ->formatStateUsing(fn ($state, Tenant $record) => new HtmlString('
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;" wire:click.stop="">
                            <!-- Master Domain -->
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-weight: 800; color: #4f46e5; font-size: 12px; letter-spacing: -0.01em;">'.$state.'</span>
                                <span style="background: #eef2ff; color: #6366f1; font-size: 8px; font-weight: 900; padding: 1px 6px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid rgba(99, 102, 241, 0.1);">MASTER</span>
                            </div>

                            <!-- Alias List -->
                            <div style="display: flex; flex-direction: column; gap: 4px; width: 100%;">
                                '. $record->domains->where('type', 'alias')->map(fn($d) => '
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="font-weight: 800; color: #4f46e5; font-size: 12px; letter-spacing: -0.01em;">'.$d->domain.'</span>
                                        <div style="cursor: pointer; color: #ef4444; opacity: 0.6; transition: all 0.2s;" 
                                             onmouseover="this.style.opacity=1; this.style.transform=\'scale(1.1)\'" onmouseout="this.style.opacity=0.6; this.style.transform=\'scale(1)\'"
                                             wire:click.stop="mountTableAction(\'delete_alias\', \''.$record->id.'\', {domain: \''.$d->domain.'\'})">
                                            <svg style="width: 12px; height: 12px;" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.34 6.62m-2.52 0L11.55 9m10.33-3.11c.18.02.36.05.53.07.42.06.74.43.74.85v1.07c0 .42-.32.79-.74.85-.17.02-.36.04-.53.07m-1.77 1.55-.42 10.45a2.25 2.25 0 0 1-2.24 2.16H7.48a2.25 2.25 0 0 1-2.24-2.16L4.82 17.5m15.02-3.55a2.25 2.25 0 0 0-2.24-2.16H6.42a2.25 2.25 0 0 0-2.24 2.16M18 4V3a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v1m12 0H6" />
                                            </svg>
                                        </div>
                                    </div>
                                ')->implode('') .'
                            </div>

                            <!-- Add Alias Action Trigger -->
                            <div style="display: inline-flex; align-items: center; gap: 4px; cursor: pointer; color: #94a3b8; margin-top: 2px; transition: all 0.2s;" 
                                 onmouseover="this.style.color=\'#64748b\'" onmouseout="this.style.color=\'#94a3b8\'"
                                 wire:click.stop="mountTableAction(\'add_alias_custom\', \''.$record->id.'\')">
                                <svg style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                                <span style="font-size: 10px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.05em;">ADD ALIAS</span>
                            </div>
                        </div>
                    ')),

                Tables\Columns\TextColumn::make('nameservers')
                    ->label('NAME SERVER')
                    ->alignCenter()
                    ->placeholder('N/A')
                    ->formatStateUsing(fn ($state) => is_array($state) ? new HtmlString('<div style="font-size: 10px; color: #64748b; line-height: 1.2;">'.implode('<br>', $state).'</div>') : 'N/A'),

                Tables\Columns\TextColumn::make('cloudflare_status')
                    ->label('CF STATUS')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'moved' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('NODE')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('status')
                    ->label('STATUS')
                    ->alignCenter()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server_id')
                    ->relationship('server', 'name')
                    ->label('Server Node'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'pending' => 'Pending',
                    ])
                    ->label('Status'),

                Tables\Filters\SelectFilter::make('cloudflare_status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                    ])
                    ->label('Cloudflare Status'),
            ])
            ->actions([
                EditAction::make()->iconButton()->modalWidth('lg')->color('gray'),

                // Action Group for Cloudflare
                ActionGroup::make([
                    Action::make('sync_cloudflare')
                        ->label('Sync Cloudflare')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->color('info')
                        ->action(function (Tenant $record) {
                            $domainName = $record->domain;
                            // Cari di semua akun CF
                            $cfAccounts = \App\Models\CloudflareAccount::all();
                            $found = false;

                            foreach ($cfAccounts as $account) {
                                $cf = new \App\Services\CloudflareService($account->api_token, $account->email, $account->api_key);
                                $result = $cf->getZoneByName($domainName);

                                if (isset($result['success']) && $result['success'] && !empty($result['result'])) {
                                    $zone = $result['result'][0];
                                    $record->update([
                                        'cloudflare_status' => $zone['status'],
                                        'nameservers' => $zone['name_servers'] ?? [],
                                    ]);
                                    $found = true;
                                    break;
                                }
                            }

                            if ($found) {
                                Notification::make()->title('Cloudflare Synced')->success()->send();
                            } else {
                                Notification::make()->title('Domain not found on any CF Account')->warning()->send();
                            }
                        }),

                    Action::make('push_dns')
                        ->label('Push DNS')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Tenant $record) {
                            if (!$record->server || !$record->server->ip) {
                                Notification::make()->title('Server IP not found')->danger()->send();
                                return;
                            }

                            $domainName = $record->domain;
                            $cfAccounts = \App\Models\CloudflareAccount::all();
                            $found = false;

                            foreach ($cfAccounts as $account) {
                                $cf = new \App\Services\CloudflareService($account->api_token, $account->email, $account->api_key);
                                $result = $cf->getZoneByName($domainName);

                                if (isset($result['success']) && $result['success'] && !empty($result['result'])) {
                                    $zone = $result['result'][0];
                                    $zoneId = $zone['id'];
                                    
                                    // Clean IP
                                    $ip = $record->server->ip;
                                    if (preg_match('/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', $ip, $matches)) {
                                        $ip = $matches[0];
                                    }

                                    // Push A Record
                                    $dnsResult = $cf->addDnsRecord($zoneId, 'A', '@', $ip);
                                    
                                    if (isset($dnsResult['success']) && $dnsResult['success']) {
                                        Notification::make()->title('DNS Pushed Successfully')->success()->send();
                                    } else {
                                        $msg = $dnsResult['errors'][0]['message'] ?? 'Unknown Error';
                                        Notification::make()->title('DNS Push Failed')->danger()->body($msg)->send();
                                    }
                                    
                                    $found = true;
                                    break;
                                }
                            }

                            if (!$found) {
                                Notification::make()->title('Domain not found on any CF Account')->warning()->send();
                            }
                        }),
                ])->icon('heroicon-m-ellipsis-vertical')->tooltip('Cloudflare Actions'),

                Action::make('add_alias_custom')
                    ->label('Add Alias')
                    ->modalHeading('Register Domain Alias')
                    ->modalDescription('Add an additional domain endpoint for this tenant.')
                    ->modalWidth('md') // Sedikit lebih lebar dari sm agar dropdown lega
                    ->modalSubmitActionLabel('Add Alias')
                    ->form([
                        Forms\Components\Select::make('domain')
                            ->label('Select Alias Domain')
                            ->required()
                            ->searchable()
                            ->options(function () {
                                $existingDomains = Tenant::pluck('domain')->toArray();
                                return \App\Models\CloudflareDomain::whereNotIn('name', $existingDomains)
                                    ->pluck('name', 'name');
                            })
                            ->placeholder('Choose domain...')
                            ->prefixIcon('heroicon-m-globe-alt'),
                    ])
                    ->action(function (Tenant $record, array $data) {
                        if (!$record->remote_id || !$record->server) {
                            Notification::make()
                                ->title('Action Restricted')
                                ->warning()
                                ->body('This tenant is not linked to a remote server.')
                                ->send();
                            return;
                        }

                        $service = new TenantService($record->server->api_endpoint, $record->server->api_key);
                        $result = $service->addDomainAlias($record->remote_id, $data['domain']);
                        
                        if (isset($result['status']) && $result['status'] === 'success') {
                            // Simpan ke database lokal
                            $record->domains()->updateOrCreate(
                                ['domain' => $data['domain']],
                                ['type' => 'alias']
                            );

                            Notification::make()
                                ->title('Domain Alias Added')
                                ->success()
                                ->body($result['message'] ?? 'Successfully registered.')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Registration Failed')
                                ->danger()
                                ->body($result['message'] ?? 'The server rejected the alias.')
                                ->send();
                        }
                    }),
                Action::make('delete_alias')
                    ->label('Delete Alias')
                    ->modalHeading('Delete Domain Alias')
                    ->modalDescription('Are you sure you want to remove this alias from the remote server?')
                    ->modalWidth('md')
                    ->modalAlignment('center')
                    ->modalFooterActionsAlignment('center')
                    ->action(function (Tenant $record, array $arguments) {
                        $domain = $arguments['domain'] ?? null;
                        
                        if (!$domain || !$record->remote_id || !$record->server) {
                            Notification::make()->title('Missing data or remote connection')->danger()->send();
                            return;
                        }

                        $service = new TenantService($record->server->api_endpoint, $record->server->api_key);
                        $result = $service->removeDomainAlias($record->remote_id, $domain);
                        
                        if (isset($result['status']) && $result['status'] === 'success') {
                            $record->domains()->where('domain', $domain)->delete();
                            Notification::make()->title('Domain Alias Removed')->success()->send();
                        } else {
                            Notification::make()
                                ->title('Removal Failed')
                                ->danger()
                                ->body($result['message'] ?? 'The server rejected the request.')
                                ->send();
                        }
                    }),

                DeleteAction::make()
                    ->iconButton()
                    ->before(function (Tenant $record, DeleteAction $action) {
                        if ($record->remote_id && $record->server) {
                            $service = new TenantService($record->server->api_endpoint, $record->server->api_key);
                            $response = $service->deleteTenant($record->remote_id);
                            
                            // Jika API Remote Gagal, Batalkan Penghapusan Lokal
                            if (isset($response['status']) && $response['status'] !== 'success') {
                                Notification::make()
                                    ->title('Remote Deletion Failed')
                                    ->danger()
                                    ->body($response['message'] ?? 'The server could not delete the tenant.')
                                    ->send();
                                
                                $action->halt(); // Menghentikan proses delete Filament
                            }
                        }
                    }),
            ])
            ->headerActions([
                // Moved to ManageTenants page header
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['domains', 'server']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTenants::route('/'),
        ];
    }
}
