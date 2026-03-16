<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class WhatsAppConnect extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Conectar WhatsApp';
    protected static ?string $title = 'Conexão WhatsApp';
    protected static ?string $navigationGroup = 'Dispositivos';

    protected static string $view = 'filament.pages.whats-app-connect';
}
