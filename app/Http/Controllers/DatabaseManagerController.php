<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseManagerController extends Controller
{
    public function index($table = null)
    {
        if (!auth()->user()->isAdmin()) abort(403);

        $tables = DB::connection()->getSchemaBuilder()->getTables();
        $tableNames = array_map(function($t) {
            // Laravel 11 returns objects, older versions might return arrays or strings
            if (is_object($t)) return $t->name;
            if (is_array($t)) return array_values($t)[0];
            return $t;
        }, $tables);

        // Filter out migration and system tables
        $tableNames = array_filter($tableNames, function($name) {
            return !in_array($name, ['migrations', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs']);
        });

        $data = null;
        $columns = [];
        if ($table && in_array($table, $tableNames)) {
            $data = DB::table($table)->orderBy('id', 'desc')->paginate(20);
            $columns = Schema::getColumnListing($table);
        }

        return view('admin.db-manager', compact('tableNames', 'table', 'data', 'columns'));
    }

    public function delete($table, $id)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        DB::table($table)->where('id', $id)->delete();
        return back()->with('success', "Registro #$id excluído de $table");
    }

    public function save(Request $request, $table, $id = null)
    {
        if (!auth()->user()->isAdmin()) abort(403);
        
        $data = $request->except(['_token', '_method']);
        
        // Basic handling for common fields
        if (isset($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        if ($id) {
            DB::table($table)->where('id', $id)->update($data);
            return back()->with('success', 'Registro atualizado!');
        } else {
            DB::table($table)->insert($data);
            return back()->with('success', 'Registro criado!');
        }
    }
}
