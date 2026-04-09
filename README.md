# Mensageria TechInteligente (SaaS WhatsApp)

Um sistema de mensageria independente para envio de mensagens via WhatsApp com suporte a API e envio manual.

## 🏗️ Arquitetura
- **Backend:** Laravel 12 (PHP 8.2+)
- **Admin:** 
- **Bridge:** Node.js + WPPConnect
- **Fila:** Redis

## 🚀 Como Rodar

### 1. Backend (Laravel)
```bash
composer install
php artisan migrate
php artisan filament:install --panels
php artisan serve
```

### 2. Bridge (WhatsApp)
```bash
cd bridge
npm install
node index.js
```

## 🔑 API
Endpoint: `POST /api/v1/send`
Header: `Authorization: Bearer YOUR_API_KEY`
Body:
```json
{
  "to": "5545999999999",
  "message": "Olá!",
  "media": "base64_or_url"
}
```
