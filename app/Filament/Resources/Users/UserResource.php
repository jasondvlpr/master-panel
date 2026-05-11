<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Components\Section;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'System Staff';
    protected static string | \UnitEnum | null $navigationGroup = 'System Control';
    protected static ?int $navigationSort = 100;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Account Information')
                    ->description('Update staff member credentials and access level.')
                    ->icon('heroicon-m-user-circle')
                    ->columns(2)
                    ->components([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->placeholder('John Doe')
                            ->prefixIcon('heroicon-m-user')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->placeholder('john@example.com')
                            ->prefixIcon('heroicon-m-envelope')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('role')
                            ->label('Account Role')
                            ->options([
                                'admin' => 'Administrator',
                                'developer' => 'Developer',
                            ])
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-m-shield-check'),
                        Forms\Components\TextInput::make('password')
                            ->label('New Password')
                            ->password()
                            ->placeholder('Leave blank to keep current')
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->revealable()
                            ->prefixIcon('heroicon-m-lock-closed'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'developer' => 'info',
                        default => 'gray',
                    }),
            ])
            ->filters([])
            ->actions([
                Actions\EditAction::make()
                    ->modalWidth('2xl')
                    ->modalHeading('Edit User Account'),
                Actions\DeleteAction::make()
                    ->hidden(fn (User $record) => $record->id === auth()->id()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
