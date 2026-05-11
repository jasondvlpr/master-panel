<?php

namespace App\Filament\Pages;

use App\Models\CloudflareAccount;
use App\Models\CloudflareDomain;
use App\Services\CloudflareService;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Collection;
use UnitEnum;
use BackedEnum;

class CloudflareDomains extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationLabel = 'Domain Management';
    protected static ?string $title = 'Domain Management';
    protected static string|UnitEnum|null $navigationGroup = 'Network & DNS';

    protected string $view = 'filament.pages.cloudflare-domains';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('sync_all')
                ->label('Sync All Accounts')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    $accounts = CloudflareAccount::all();
                    $totalSynced = 0;
                    
                    // Prevent timeout for large accounts
                    set_time_limit(300);

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

                    \Filament\Notifications\Notification::make()
                        ->title('Global Sync Completed')
                        ->success()
                        ->body($totalSynced . ' domains have been synced across all accounts.')
                        ->send();
                }),

            \Filament\Actions\Action::make('add_domain')
                ->label('Add Domain')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Register New Domain to Cloudflare')
                ->modalWidth('md')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Domain Name')
                        ->placeholder('example.com')
                        ->required(),
                    \Filament\Forms\Components\Select::make('cloudflare_account_id')
                        ->label('Cloudflare Account')
                        ->options(CloudflareAccount::pluck('name', 'id'))
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    $account = CloudflareAccount::find($data['cloudflare_account_id']);
                    $cf = new CloudflareService($account->api_token, $account->email, $account->api_key);
                    
                    $result = $cf->addZone($data['name']);

                    if (isset($result['success']) && $result['success']) {
                        $zone = $result['result'];
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

                        \Filament\Notifications\Notification::make()
                            ->title('Domain Added Successfully')
                            ->success()
                            ->send();
                    } else {
                        $error = $result['errors'][0]['message'] ?? 'Cloudflare API Error';
                        \Filament\Notifications\Notification::make()
                            ->title('Failed to Add Domain')
                            ->danger()
                            ->body($error)
                            ->send();
                    }
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(CloudflareDomain::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Domain Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'deactivated' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('name_servers')
                    ->label('Name Servers')
                    ->listWithLineBreaks()
                    ->bulleted(),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('cloudflare_account_id')
                    ->label('Account')
                    ->relationship('cloudflareAccount', 'name'),
            ])
            ->actions([
                \Filament\Actions\Action::make('view_dns')
                    ->label('View DNS')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->modalHeading(fn(CloudflareDomain $record) => "DNS Records: " . $record->name)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalContent(function (CloudflareDomain $record) {
                        return view('filament.pages.dns-records-modal', [
                            'record' => $record
                        ]);
                    })
            ]);
    }
}
