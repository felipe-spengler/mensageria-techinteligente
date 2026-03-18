<?php

namespace App\Filament\Resources\ApiKeyResource\Pages;

use App\Filament\Resources\ApiKeyResource;
use Filament\Resources\Pages\ListRecords;

class ListApiKeys extends ListRecords
{
    protected static string $resource = ApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('config_asaas')
                ->label('Configurar Conta Asaas')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->visible(fn () => auth()->user()->isAdmin())
                ->form([
                    \Filament\Forms\Components\TextInput::make('asaas_key')
                        ->label('Sua API Key do Asaas')
                        ->password()
                        ->default(\App\Models\Setting::getValue('asaas_key'))
                        ->required(),
                    \Filament\Forms\Components\Select::make('asaas_mode')
                        ->label('Modo de Operação')
                        ->options(['sandbox' => 'Teste (Sandbox)', 'production' => 'Real (Produção)'])
                        ->default(\App\Models\Setting::getValue('asaas_mode', 'sandbox'))
                        ->required(),
                ])
                ->action(function (array $data) {
                    \App\Models\Setting::setValue('asaas_key', $data['asaas_key'], 'asaas');
                    \App\Models\Setting::setValue('asaas_mode', $data['asaas_mode'], 'asaas');
                    \Filament\Notifications\Notification::make()
                        ->title('Configurações Asaas salvas com sucesso!')
                        ->success()
                        ->send();
                }),
            
            \Filament\Actions\Action::make('api_docs')
                ->label('Como Usar a API')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->modalHeading('Documentação Rápida da API')
                ->modalWidth('2xl')
                ->modalSubmitAction(false)
                ->modalContent(view('components.api-docs-modal'))
        ];
    }
}
