<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CloudflareAccountResource\Pages;
use App\Models\CloudflareAccount;
use App\Models\CloudflareDomain;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;

class CloudflareAccountResource extends Resource
{
    protected static ?string $model = CloudflareAccount::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Cloudflare API';
    protected static string|\UnitEnum|null $navigationGroup = 'Network & DNS';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Account Name')
                    ->placeholder('e.g. Personal Account')
                    ->prefixIcon('heroicon-m-user')
                    ->required()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('email')
                    ->label('Email Address')
                    ->email()
                    ->placeholder('your-email@domain.com')
                    ->prefixIcon('heroicon-m-envelope')
                    ->required()
                    ->columnSpan(2),
                Forms\Components\TextInput::make('api_key')
                    ->label('Global API Key')
                    ->password()
                    ->revealable()
                    ->prefixIcon('heroicon-m-key')
                    ->columnSpan(2),
                Forms\Components\TextInput::make('api_token')
                    ->label('API Token (Optional)')
                    ->password()
                    ->revealable()
                    ->prefixIcon('heroicon-m-lock-closed')
                    ->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->icon('heroicon-m-envelope')
                    ->iconColor('primary')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('domains_count')
                    ->label('Domains')
                    ->counts('domains')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\Action::make('sync_domains')
                    ->iconButton()
                    ->tooltip('Sync Domains')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (CloudflareAccount $record) {
                        $cf = new \App\Services\CloudflareService($record->api_token, $record->email, $record->api_key);
                        $response = $cf->getZones();

                        if (isset($response['success']) && $response['success']) {
                            foreach ($response['result'] as $zone) {
                                // Ambil subdomain (Record A/CNAME)
                                $dnsResponse = $cf->getDnsRecords($zone['id']);
                                $subdomains = [];
                                if (isset($dnsResponse['success']) && $dnsResponse['success']) {
                                    foreach ($dnsResponse['result'] as $dns) {
                                        if (in_array($dns['type'], ['A', 'CNAME'])) {
                                            $sub = str_replace('.' . $zone['name'], '', $dns['name']);
                                            if ($sub !== $zone['name'] && $sub !== '@' && $sub !== $dns['name']) {
                                                $subdomains[] = $sub;
                                            }
                                        }
                                    }
                                }

                                \App\Models\CloudflareDomain::updateOrCreate(
                                    ['zone_id' => $zone['id']],
                                    [
                                        'cloudflare_account_id' => $record->id,
                                        'name' => $zone['name'],
                                        'status' => $zone['status'],
                                        'name_servers' => $zone['name_servers'] ?? [],
                                        'paused' => $zone['paused'] ?? false,
                                        'subdomains' => array_unique($subdomains),
                                        'last_synced_at' => now(),
                                    ]
                                );
                            }

                            Notification::make()
                                ->title('Sync Completed')
                                ->success()
                                ->body(count($response['result']) . ' domains have been synced.')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sync Failed')
                                ->danger()
                                ->body($response['errors'][0]['message'] ?? 'Unknown error')
                                ->send();
                        }
                    }),
                Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit Account')
                    ->modalWidth('2xl'),
                Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete Account'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCloudflareAccounts::route('/'),
        ];
    }
}
