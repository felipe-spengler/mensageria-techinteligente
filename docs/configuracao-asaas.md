# Configuração do Asaas para Pagamentos PIX

## Problema Identificado

O sistema estava aprovando pagamentos automaticamente sem verificar se o PIX foi realmente pago. Isso acontecia porque havia uma lógica de **teste/simulação** no método `checkStatus()` do `ManualSendController` que aprovava qualquer transação consultada.

## Correções Realizadas

### 1. Removida a Auto-Aprovação de Pagamentos

**Arquivo:** `app/Http/Controllers/ManualSendController.php`

Removida a lógica que aprovava automaticamente pagamentos no método `checkStatus()`. Agora o método apenas retorna o status real da transação armazenada no banco de dados.

### 2. Integração com API do Asaas

**Arquivo:** `app/Http/Controllers/PlanController.php`

Adicionada integração completa com a API do Asaas para:
- Criar ou buscar cliente no Asaas
- Gerar cobrança PIX real
- Obter QR Code e payload PIX

### 3. Melhorias na Interface de Pagamento

**Arquivo:** `resources/views/purchase.blade.php`

- Exibição do QR Code real gerado pelo Asaas
- Opção de copiar o código PIX (Copia e Cola)
- Melhor tratamento de erros

## Como Configurar o Asaas

### Passo 1: Criar Conta no Asaas

1. Acesse [https://www.asaas.com](https://www.asaas.com)
2. Crie uma conta gratuita
3. Ative sua conta PIX no Asaas

### Passo 2: Obter API Key

1. Acesse o painel do Asaas
2. Vá em **Integrações** > **API Key**
3. Copie sua API Key

**Importante:** 
- Para testes, use a API Key do ambiente **Sandbox**: `https://sandbox.asaas.com`
- Para produção, use a API Key do ambiente **Produção**: `https://www.asaas.com`

### Passo 3: Configurar no Sistema

1. Faça login no painel administrativo: `/admin/login`
2. Acesse a página de configurações financeiras (ou WhatsApp, dependendo de onde está configurado)
3. Configure:
   - **Asaas API Key**: Cole sua API Key
   - **Asaas Enabled**: Marque como ativo
   - **Asaas Webhook Token**: Gere um token seguro (ex: `wh_` + string aleatória)

### Passo 4: Configurar Webhook no Asaas

1. No painel do Asaas, vá em **Integrações** > **Webhooks**
2. Adicione um novo webhook com a URL:
   ```
   https://seu-dominio.com/api/v1/webhook/asaas
   ```
3. Selecione os eventos:
   - `PAYMENT_RECEIVED` (Pagamento recebido)
   - `PAYMENT_CONFIRMED` (Pagamento confirmado)
   - `PAYMENT_OVERDUE` (Pagamento vencido)
4. Configure o token de autenticação (mesmo que configurou no sistema)

### Passo 5: Testar

1. Acesse a página de planos: `/`
2. Escolha um plano e clique em "Contratar"
3. Preencha os dados e clique em "Gerar PIX de Pagamento"
4. Um QR Code real será gerado
5. Faça o pagamento via PIX
6. O webhook do Asaas notificará o sistema automaticamente
7. O acesso será liberado após confirmação do pagamento

## Ambiente de Testes (Sandbox)

No ambiente sandbox do Asaas, você pode simular pagamentos:

1. Use a API Key do sandbox
2. Gere uma cobrança PIX
3. No painel do Asaas Sandbox, você pode marcar manualmente a cobrança como paga
4. O webhook será disparado automaticamente

## Estrutura do Webhook

O webhook do Asaas envia dados no formato:

```json
{
  "event": "PAYMENT_RECEIVED",
  "payment": {
    "id": "pay_123456",
    "value": 99.90,
    "status": "RECEIVED",
    "externalReference": "PLAN-ABC12345"
  }
}
```

O sistema processa esses dados no `AsaasWebhookController` e:
1. Busca a transação pelo `externalReference` (txid)
2. Atualiza o status para "paid"
3. Ativa o plano do usuário
4. Gera ou atualiza a API Key

## Segurança

- O webhook valida o token de autenticação enviado no header `asaas-access-token`
- Todas as transações são registradas no banco de dados
- Logs detalhados são gerados para auditoria

## Troubleshooting

### Pagamento não é confirmado automaticamente

1. Verifique se o webhook está configurado corretamente no Asaas
2. Verifique os logs do Laravel: `storage/logs/laravel.log`
3. Teste o webhook manualmente usando ferramentas como Postman
4. Verifique se a URL do webhook está acessível publicamente

### QR Code não é gerado

1. Verifique se a API Key do Asaas está correta
2. Verifique se o Asaas está habilitado nas configurações
3. Verifique os logs para erros de API
4. Certifique-se de que sua conta Asaas tem PIX ativado

### Erro ao criar cliente no Asaas

1. Verifique se os dados do usuário estão completos (nome, email, telefone)
2. O CPF/CNPJ é obrigatório - atualmente está usando o telefone como fallback
3. Considere adicionar um campo de CPF no cadastro

## Próximos Passos Recomendados

1. Adicionar campo de CPF no cadastro de usuários
2. Implementar renovação automática de assinaturas
3. Adicionar notificações por email quando o pagamento for confirmado
4. Implementar sistema de cancelamento de assinaturas
5. Adicionar relatórios financeiros no painel admin
