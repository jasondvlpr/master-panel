<?php

namespace App\Filament\Resources\CloudflareAccountResource\Pages;

use App\Filament\Resources\CloudflareAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCloudflareAccounts extends ManageRecords
{
    protected static string $resource = CloudflareAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('2xl'),
        ];
    }
}
