@extends('layouts.admin')

@section('title', 'Planos de Venda')

@section('content')
    <div class="flex flex-col space-y-12" x-data="{ planModal: false, editPlanData: null }">
        
        <!-- Toolbar -->
        <div class="flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-white tracking-tight">Gerenciamento de Planos</h3>
                <p class="text-sm text-gray-400">Crie, edite e remova os planos de assinatura disponíveis.</p>
            </div>
            <div class="flex items-center space-x-4">
                <button @click="editPlanData = null; planModal = true" class="flex items-center space-x-2 bg-blue-600/10 border border-blue-500/20 text-blue-400 px-6 py-3 rounded-2xl text-xs font-bold hover:bg-blue-600/20 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Novo Plano</span>
                </button>
            </div>
        </div>

        <!-- Plans Table Card -->
        <div class="glass rounded-[40px] border-dash-700 overflow-hidden shadow-2xl">
            @if($plans->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-dash-900/50 border-b border-white/5">
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Nome do Plano</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Preço</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Limite Msgs</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Tipo</th>
                                <th class="px-8 py-6 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($plans as $plan)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="px-8 py-6">
                                        <p class="text-sm font-bold text-white">{{ $plan->name }}</p>
                                        <p class="text-[10px] text-gray-400 truncate max-w-xs">{{ $plan->description }}</p>
                                    </td>
                                    <td class="px-8 py-6 text-sm text-blue-400 font-bold">R$ {{ number_format($plan->price, 2, ',', '.') }}</td>
                                    <td class="px-8 py-6 text-sm text-gray-300 font-mono">{{ $plan->message_limit }} / mês</td>
                                    <td class="px-8 py-6 text-sm">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-[10px] font-bold uppercase tracking-wide">
                                            {{ $plan->type }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-6 text-sm text-center">
                                        <div class="flex items-center justify-center space-x-3">
                                            <button @click="editPlanData = {{ json_encode($plan) }}; planModal = true" class="w-8 h-8 rounded-xl bg-dash-800 flex items-center justify-center text-gray-400 hover:text-white hover:border-blue-500/30 border border-transparent transition-all">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <form action="{{ route('admin.plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja deletar este plano?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="w-8 h-8 rounded-xl bg-dash-800 flex items-center justify-center text-gray-400 hover:text-red-400 border border-transparent hover:bg-red-500/10 hover:border-red-500/30 transition-all">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-20 flex flex-col items-center justify-center text-center">
                    <div class="w-20 h-20 bg-dash-900 border border-white/5 rounded-[32px] flex items-center justify-center mb-6">
                        <svg class="w-10 h-10 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    </div>
                    <h4 class="text-xl font-bold text-white mb-2">Nenhum Plano Criado</h4>
                    <p class="text-sm text-gray-500 mb-8 max-w-sm">Crie seu primeiro plano de assinatura para habilitar pagamentos PIX/Asaas.</p>
                </div>
            @endif
        </div>

        <!-- Plan Form Modal -->
        <div x-show="planModal" class="fixed inset-0 z-[60] flex items-center justify-center p-6 bg-dash-950/80 backdrop-blur-sm" x-cloak>
            <div class="glass w-full max-w-lg rounded-[40px] p-10 border-dash-700 shadow-3xl" @click.away="planModal = false">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-xl font-bold text-white" x-text="editPlanData ? 'Editar Plano' : 'Novo Plano'"></h3>
                    <button @click="planModal = false" class="text-gray-500 hover:text-white"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg></button>
                </div>
                
                <form :action="editPlanData ? `/admin/plans/${editPlanData.id}` : '{{ route('admin.plans.store') }}'" method="POST" class="space-y-6">
                    @csrf
                    <template x-if="editPlanData"><input type="hidden" name="_method" value="PUT"></template>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Nome do Plano</label>
                        <input type="text" name="name" :value="editPlanData?.name || ''" required class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="Ex: Starter">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Preço (R$)</label>
                            <input type="number" step="0.01" name="price" :value="editPlanData?.price || ''" required class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="99.90">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Limite Mensal</label>
                            <input type="number" name="message_limit" :value="editPlanData?.message_limit || ''" required class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all" placeholder="1000">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Tipo (ex: SaaS, API)</label>
                        <input type="text" name="type" :value="editPlanData?.type || 'SaaS'" required class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2">Descrição Curta</label>
                        <textarea name="description" rows="2" required x-text="editPlanData?.description || ''" class="w-full bg-dash-950 border border-white/5 rounded-2xl p-4 text-white focus:ring-2 focus:ring-blue-500 outline-none transition-all placeholder-gray-600" placeholder="Benefícios e recursos do plano..."></textarea>
                    </div>
                    
                    <button type="submit" class="w-full btn-grad py-5 rounded-3xl font-bold text-sm shadow-xl shadow-blue-900/30" x-text="editPlanData ? 'Salvar Alterações' : 'Criar Plano'"></button>
                </form>
            </div>
        </div>
    </div>
@endsection
