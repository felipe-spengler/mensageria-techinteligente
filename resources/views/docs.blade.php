@extends('layouts.admin')

@section('title', 'Documentação da API')

@section('content')
<div class="max-w-4xl mx-auto space-y-12 pb-20">
    
    <!-- Intro -->
    <div class="space-y-4">
        <h3 class="text-3xl font-bold text-white tracking-tight">Guia de Integração</h3>
        <p class="text-gray-400">Bem-vindo à API da TechInteligente. Integre o envio de mensagens de WhatsApp ao seu sistema de forma rápida e segura. Nossa API é baseada em requisições REST utilizando o protocolo HTTP POST.</p>
    </div>

    <!-- Auth -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="glass p-8 rounded-[32px] border-dash-700">
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-12 h-12 bg-blue-600/10 rounded-2xl flex items-center justify-center border border-blue-500/20">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
                <h4 class="text-xl font-bold text-white">Autenticação</h4>
            </div>
            <p class="text-gray-400 text-sm mb-6">Inclua seu <span class="text-blue-400">Bearer Token</span> no cabeçalho <span class="font-mono bg-black/30 px-2 py-1 rounded">Authorization</span> ou via parâmetro <span class="font-mono bg-black/30 px-2 py-1 rounded">api_key</span>.</p>
            <div class="bg-black/40 rounded-2xl p-6 border border-white/5 overflow-x-auto">
                <pre class="text-xs text-blue-300 font-mono">Authorization: Bearer sua_chave_api_aqui</pre>
            </div>
        </div>

        <div class="glass p-8 rounded-[32px] border-dash-700">
            <div class="flex items-center space-x-4 mb-6">
                <div class="w-12 h-12 bg-emerald-600/10 rounded-2xl flex items-center justify-center border border-emerald-500/20">
                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <h4 class="text-xl font-bold text-white">Fluxo de Envio</h4>
            </div>
            <p class="text-gray-400 text-sm mb-4">O envio é **assíncrono**. Ao postar uma mensagem:</p>
            <ul class="text-[11px] text-gray-500 space-y-2 list-disc pl-4">
                <li>A API responde imediatamente com <span class="text-emerald-400">queued</span> e um <span class="font-mono">log_id</span>.</li>
                <li>Nossa fila processa e tenta a entrega via WhatsApp.</li>
                <li>Você pode consultar o status final (`sent` ou `failed`) no endpoint de **Logs**.</li>
            </ul>
        </div>
    </div>

    <!-- Endpoint: Send -->
    <div class="space-y-6">
        <div class="flex items-center space-x-3">
            <span class="bg-emerald-600 px-3 py-1 rounded text-[10px] font-black text-white uppercase tracking-tighter">POST</span>
            <h4 class="text-xl font-bold text-white">Enviar Mensagem</h4>
        </div>
        <div class="bg-dash-900 border border-white/5 p-4 rounded-xl font-mono text-sm text-blue-400">
            {{ url('/api/v1/send') }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-6">
                <h5 class="text-sm font-bold text-gray-300 uppercase tracking-widest">Parâmetros (JSON)</h5>
                <table class="w-full text-left text-sm text-gray-400">
                    <thead class="text-[10px] uppercase font-bold text-gray-600">
                        <tr>
                            <th class="pb-4">Campo</th>
                            <th class="pb-4">Tipo</th>
                            <th class="pb-4">Descrição</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr>
                            <td class="py-4 font-mono text-blue-400">to</td>
                            <td class="py-4">String</td>
                            <td class="py-4">Número com DDD (ex: 45999998888)</td>
                        </tr>
                        <tr>
                            <td class="py-4 font-mono text-blue-400">message</td>
                            <td class="py-4">Text</td>
                            <td class="py-4">Conteúdo da mensagem (UTF-8)</td>
                        </tr>
                        <tr>
                            <td class="py-4 font-mono text-gray-500">media</td>
                            <td class="py-4">URL</td>
                            <td class="py-4 text-xs italic">Opcional. Link direto para imagem (JPG/PNG) ou PDF.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="space-y-4">
                <h5 class="text-sm font-bold text-gray-300 uppercase tracking-widest">Exemplo cURL</h5>
                <div class="bg-black/60 rounded-2xl p-6 border border-white/10 text-[11px] font-mono leading-relaxed text-blue-200 overflow-x-auto">
<pre>
curl -X POST "{{ url('/api/v1/send') }}" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "45920014605",
    "message": "Olá! Sua reserva foi confirmada. ✅",
    "media": "https://meusite.com/voucher.pdf"
  }'
</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint: QR Code -->
    <div class="space-y-6 pt-8 border-t border-white/5">
        <div class="flex items-center space-x-3">
            <span class="bg-blue-600 px-3 py-1 rounded text-[10px] font-black text-white uppercase tracking-tighter">GET</span>
            <h4 class="text-xl font-bold text-white">Obter QR Code</h4>
        </div>
        <p class="text-gray-400 text-sm">Retorna a imagem PNG do QR Code para conexão da sua instância. Útil para embutir o pareamento diretamente no seu sistema.</p>
        <div class="bg-dash-900 border border-white/5 p-4 rounded-xl font-mono text-sm text-blue-400">
            {{ url('/api/v1/qrcode') }}
        </div>
        <div class="bg-black/60 rounded-2xl p-6 border border-white/10 text-[11px] font-mono leading-relaxed text-blue-200 overflow-x-auto">
<pre>
&lt;!-- Exemplo de uso em HTML --&gt;
&lt;img src="{{ url('/api/v1/qrcode') }}?api_key=YOUR_API_KEY" alt="Conectar WhatsApp" /&gt;

&lt;!-- Nota: O middleware também aceita api_key via query string para facilitar o uso em tags &lt;img&gt; --&gt;
</pre>
        </div>
    </div>

    <!-- Endpoint: Logs -->
    <div class="space-y-6 pt-8 border-t border-white/5">
        <div class="flex items-center space-x-3">
            <span class="bg-blue-600 px-3 py-1 rounded text-[10px] font-black text-white uppercase tracking-tighter">GET</span>
            <h4 class="text-xl font-bold text-white">Logs de Mensagens</h4>
        </div>
        <p class="text-gray-400 text-sm">Retorna o histórico de mensagens enviadas e seus respectivos status.</p>
        <div class="bg-dash-900 border border-white/5 p-4 rounded-xl font-mono text-sm text-blue-400">
            {{ url('/api/v1/logs') }}
        </div>
        <div class="bg-black/60 rounded-2xl p-6 border border-white/10 text-[11px] font-mono leading-relaxed text-blue-200 overflow-x-auto">
<pre>
curl -X GET "{{ url('/api/v1/logs') }}" \
  -H "Authorization: Bearer YOUR_API_KEY"
</pre>
        </div>
    </div>

    <!-- Important Notes -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white/5 p-6 rounded-3xl border border-white/5 space-y-3">
            <h6 class="text-xs font-bold text-white uppercase tracking-widest">Horário Comercial</h6>
            <p class="text-[11px] text-gray-400 leading-relaxed">Mensagens enviadas fora do horário comercial (08h-18h) serão rejeitadas caso a configuração de "Horário Comercial" esteja ativa no seu painel.</p>
        </div>
        <div class="bg-white/5 p-6 rounded-3xl border border-white/5 space-y-3">
            <h6 class="text-xs font-bold text-white uppercase tracking-widest">Limite de Plano</h6>
            <p class="text-[11px] text-gray-400 leading-relaxed">Você receberá um erro <span class="text-red-400">403 Forbidden</span> caso atinja o limite de mensagens mensal ou tente enviar mídia em um plano não suportado.</p>
        </div>
        <div class="bg-white/5 p-6 rounded-3xl border border-white/5 space-y-3">
            <h6 class="text-xs font-bold text-white uppercase tracking-widest">Webhook de Status</h6>
            <p class="text-[11px] text-gray-400 leading-relaxed">O sistema envia notificações automáticas via WhatsApp se houver falhas críticas na entrega, mantendo você informado sobre sua saúde de disparo.</p>
        </div>
    </div>

</div>
@endsection
