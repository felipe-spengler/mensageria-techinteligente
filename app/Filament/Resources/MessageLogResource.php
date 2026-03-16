<?php

namespace App\Filament\Resources;

use App\Models\MessageLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\MessageLogResource\Pages;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Relatório de Envios';
    protected static ?string $modelLabel = 'Log de Envio';
    protected static ?string $pluralModelLabel = 'Relatório de Envios';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->when(!auth()->user()->is_admin, function ($query) {
                $query->whereHas('apiKey', fn($q) => $q->where('user_id', auth()->id()));
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('apiKey.user.name')
                    ->label('Usuário')
                    ->visible(fn () => auth()->user()->is_admin),
                Tables\Columns\TextColumn::make('apiKey.plan.name')->label('Plano'),
                Tables\Columns\TextColumn::make('to')->label('Destinatário')->searchable(),
                Tables\Columns\TextColumn::make('message')->label('Mensagem')->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'gray',
                        'sent' => 'success',
                        'failed' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('sent_at')->label('Enviado em')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->label('Criado em')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Fila',
                        'sent' => 'Enviado',
                        'failed' => 'Erro',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMessageLogs::route('/'),
        ];
    }
}
