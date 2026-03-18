<div class="space-y-6 text-sm">
    <div class="p-4 bg-gray-900 border border-gray-800 rounded-xl">
        <h4 class="font-bold text-blue-400 mb-2">Endpoint de Envio</h4>
        <code class="text-xs bg-black px-2 py-1 rounded">POST {{ url('/api/v1/send') }}</code>
    </div>

    <div x-data="{ tab: 'curl' }">
        <div class="flex space-x-1 mb-2 bg-gray-100 p-1 rounded-lg">
            <button type="button" @click="tab = 'curl'" :class="tab === 'curl' ? 'bg-white shadow text-blue-600' : 'text-gray-500'" class="px-3 py-1 text-xs font-bold rounded-md">cURL</button>
            <button type="button" @click="tab = 'php'" :class="tab === 'php' ? 'bg-white shadow text-blue-600' : 'text-gray-500'" class="px-3 py-1 text-xs font-bold rounded-md">PHP (Guzzle/Http)</button>
            <button type="button" @click="tab = 'js'" :class="tab === 'js' ? 'bg-white shadow text-blue-600' : 'text-gray-500'" class="px-3 py-1 text-xs font-bold rounded-md">JS (Axios)</button>
        </div>

        <div x-show="tab === 'curl'" class="p-4 bg-black rounded-xl overflow-x-auto text-green-400 font-mono text-xs">
<pre>curl -X POST "{{ url('/api/v1/send') }}" \
  -H "Authorization: Bearer SUA_CHAVE_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "to": "5545999999999",
    "message": "Olá via API Mensageria!",
    "media": "URL_OU_BASE64_OPCIONAL"
}'</pre>
        </div>

        <div x-show="tab === 'php'" class="p-4 bg-black rounded-xl overflow-x-auto text-blue-300 font-mono text-xs">
<pre>use Illuminate\Support\Facades\Http;

$response = Http::withToken('SUA_CHAVE_AQUI')
    ->post('{{ url('/api/v1/send') }}', [
        'to' => '5545999999999',
        'message' => 'Olá via API Mensageria!',
        'media' => null
    ]);

return $response->json();</pre>
        </div>

        <div x-show="tab === 'js'" class="p-4 bg-black rounded-xl overflow-x-auto text-yellow-200 font-mono text-xs">
<pre>const axios = require('axios');

axios.post('{{ url('/api/v1/send') }}', {
    to: '5545999999999',
    message: 'Olá via API Mensageria!',
    media: null
}, {
    headers: { 'Authorization': 'Bearer SUA_CHAVE_AQUI' }
}).then(res => console.log(res.data));</pre>
        </div>
    </div>

    <div class="bg-yellow-500/10 border border-yellow-500/20 p-4 rounded-xl text-yellow-600 text-xs">
        <p class="font-bold mb-1">Dica:</p>
        <p>Utilize sempre o formato internacional no campo "to" (ex: 55 + DDD + Numero). O sistema aceita envios em massa separando os números por vírgula se você estiver usando o método manual.</p>
    </div>
</div>
