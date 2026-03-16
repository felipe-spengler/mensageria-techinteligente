# WhatsApp Bridge (WPPConnect)

Este componente é responsável por manter a conexão com o WhatsApp e processar a fila de mensagens enviadas pelo Laravel.

## 🛠️ Tecnologias
- Node.js
- WPPConnect (@wppconnect-team/wppconnect)
- Redis

## ⚙️ Configuração
As configurações são feitas no arquivo `.env`.

## 🚀 Como Iniciar
```bash
npm install
node index.js
```

Após iniciar, o terminal exibirá um QR Code. Escaneie-o com o seu WhatsApp para vincular o sistema.

## 📡 Integração com Redis
O bridge escuta a lista `wpp_messages` no Redis.
O formato esperado é:
```json
{
  "to": "5545999999999",
  "message": "Texto aqui",
  "media": "base64_ou_url"
}
```
