<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\PixTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualSendController extends Controller
{
    public function index()
    {
        return view('manual-send');
    }

    public function store(Request $request)
    {
        $request->validate([
            'to' => 'required',
            'message' => 'required',
            'media' => 'nullable|string',
        ]);

        // Mock PIX generation for now
        $txid = Str::random(20);
        $amount = 5.00; // Example: R$ 5,00 por envio manual

        $transaction = PixTransaction::create([
            'amount' => $amount,
            'status' => 'pending',
            'txid' => $txid,
            'metadata' => [
                'to' => $request->to,
                'message' => $request->message,
                'media' => $request->media,
            ]
        ]);

        return response()->json([
            'success' => true,
            'pix_code' => '00020126580014br.gov.bcb.pix...', // Mock PIX code
            'txid' => $txid,
            'transaction_id' => $transaction->id
        ]);
    }

    public function checkStatus($txid)
    {
        $transaction = PixTransaction::where('txid', $txid)->firstOrFail();
        
        return response()->json([
            'status' => $transaction->status,
        ]);
    }
}
