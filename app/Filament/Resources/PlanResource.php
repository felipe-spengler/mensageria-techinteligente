<?php

namespace App\Filament\Resources;

use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Planos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->label('Nome do Plano'),
                Forms\Components\TextInput::make('message_limit')->numeric()->required()->label('Limite de Mensagens'),
                Forms\Components\Select::make('type')
                    ->options([
                        'text' => 'Apenas Texto',
                        'media' => 'Texto + Mídia',
                    ])->required()->label('Tipo'),
                Forms\Components\TextInput::make('price')->numeric()->prefix('R$')->required()->label('Preço'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome'),
                Tables\Columns\TextColumn::make('message_limit')->label('Limite'),
                Tables\Columns\TextColumn::make('type')->label('Tipo'),
                Tables\Columns\TextColumn::make('price')->money('BRL')->label('Preço'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
        ];
    }
}
