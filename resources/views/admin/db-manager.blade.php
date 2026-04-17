@extends('layouts.admin')

@section('title', 'Gerenciador de Banco de Dados')

@section('content')
<div class="flex flex-col md:flex-row gap-8 min-h-[70vh]" x-data="{ editing: null, newData: {} }">
    
    <!-- Sidebar: Tabelas -->
    <div class="w-full md:w-64 space-y-2">
        <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest px-4 mb-4">Tabelas</h4>
        @foreach($tableNames as $name)
            <a href="{{ route('admin.db_manager', $name) }}" 
               class="flex items-center space-x-3 p-3 rounded-2xl transition-all {{ $table === $name ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/40' : 'glass hover:bg-dash-800 text-gray-400' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                <span class="text-xs font-bold capitalize">{{ str_replace('_', ' ', $name) }}</span>
            </a>
        @endforeach
    </div>

    <!-- Main Content: Dados -->
    <div class="flex-1">
        @if($table)
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-2xl font-bold text-white capitalize">{{ str_replace('_', ' ', $table) }}</h3>
                    <p class="text-gray-400 text-xs mt-1">Gerenciamento direto de registros</p>
                </div>
                <!-- NOVO REGISTRO (Placeholder for now, keeping it simple) -->
            </div>

            <div class="glass rounded-[32px] overflow-hidden border-dash-700 shadow-2xl overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-dash-900/50 border-b border-white/5">
                            @foreach($columns as $col)
                                <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest">{{ $col }}</th>
                            @endforeach
                            <th class="p-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach($data as $row)
                            <tr class="hover:bg-white/[0.02] transition-colors group">
                                @foreach($columns as $col)
                                    <td class="p-4 text-xs text-gray-300 font-mono max-w-[200px] truncate">
                                        {{ is_null($row->$col) ? 'NULL' : (is_string($row->$col) ? $row->$col : json_encode($row->$col)) }}
                                    </td>
                                @endforeach
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <form action="{{ route('admin.db_manager.delete', [$table, $row->id]) }}" method="POST" onsubmit="return confirm('Tem certeza? Essa ação é irreversível.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 bg-red-500/10 text-red-500 rounded-xl hover:bg-red-500/20 transition">
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

            <div class="mt-8">
                {{ $data->links() }}
            </div>
        @else
            <div class="h-full flex flex-col items-center justify-center text-center p-12 glass rounded-[40px] border-dashed border-2 border-dash-700">
                <div class="w-20 h-20 bg-dash-800 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Selecione uma Tabela</h3>
                <p class="text-gray-500 text-sm max-w-xs">Escolha uma tabela ao lado para visualizar e gerenciar os dados brutos do sistema.</p>
            </div>
        @endif
    </div>
</div>

<style>
    /* Pagination Overrides */
    .pagination { @apply flex space-x-2; }
    .page-item { @apply rounded-xl overflow-hidden; }
    .page-link { @apply block px-4 py-2 bg-dash-900 border border-white/5 text-gray-400 hover:bg-dash-800 transition; }
    .active .page-link { @apply bg-blue-600 text-white border-blue-600; }
</style>
@endsection
