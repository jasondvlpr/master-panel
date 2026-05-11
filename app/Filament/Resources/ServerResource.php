<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServerResource\Pages;
use App\Models\Server;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationLabel = 'Web Servers';
    protected static string | \UnitEnum | null $navigationGroup = 'Infrastructure';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Server Name')
                    ->placeholder('e.g. Production Node 01')
                    ->required(),
                Forms\Components\TextInput::make('ip')
                    ->label('IP Address')
                    ->placeholder('123.123.123.123')
                    ->required(),
                Forms\Components\TextInput::make('api_endpoint')
                    ->label('Tenant API Endpoint')
                    ->placeholder('https://api.yourserver.com/v1')
                    ->required()
                    ->helperText('URL API untuk pembuatan tenant di server ini.'),
                Forms\Components\TextInput::make('api_key')
                    ->label('API Key / Secret')
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold')
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ip')
                    ->label('IP')
                    ->icon('heroicon-m-globe-alt')
                    ->iconColor('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('api_endpoint')
                    ->label('API Endpoint')
                    ->color('gray')
                    ->limit(30),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make()->modalWidth('sm'),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageServers::route('/'),
        ];
    }
}
