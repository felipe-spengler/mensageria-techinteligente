<?php

namespace App\Filament\Resources;

use App\Models\ApiKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Filament\Resources\ApiKeyResource\Pages;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Pagamentos & API';
    protected static ?string $modelLabel = 'Chave de API';
    protected static ?string $pluralModelLabel = 'Pagamentos & API';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('Usuário'),
                Forms\Components\Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required()
                    ->label('Plano'),
                Forms\Components\TextInput::make('key')
                    ->default(fn () => 'sk_' . Str::random(32))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->label('Chave'),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Ativa',
                        'inactive' => 'Inativa',
                        'suspended' => 'Suspensa',
                    ])
                    ->required()
                    ->label('Status'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expira em'),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()->is_admin, fn ($query) => $query->where('user_id', auth()->id()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->visible(fn () => auth()->user()->is_admin),
                Tables\Columns\TextColumn::make('plan.name')->label('Plano'),
                Tables\Columns\TextColumn::make('key')->label('Chave')->limit(15)->copyable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('expires_at')->label('Expiração')->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn () => auth()->user()->is_admin),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
        ];
    }
}
