<?php

namespace App\Filament\Resources\Users\UserResource\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalWidth('sm'),
        ];
    }
}
