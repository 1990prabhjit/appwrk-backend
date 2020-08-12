<?php

namespace App\Http\Controllers;

use App\Transactions;
use Illuminate\Http\Request;
use Validator;

class TransactionsController extends Controller
{
    public function getTransactions() {
        $transactions = Transactions::select('id', 'amount', 'description', 'type', 'balance', 'created_at as date')->orderBy('created_at', 'desc')->get()->transform(function ($el) {
            $el->credit = $el->type == 'credit' ? $el->amount : null;
            $el->debit = $el->type == 'debit' ? $el->amount : null;
            unset($el->amount);
            unset($el->type);
            return $el;
        });
        return response()->json(['success' => 1, 'message' => 'success', 'data' => $transactions], 200);
    }

    public function addTransaction(Request $request) {
        $validator = Validator::make($request->all(),[
            'type'=>'required',
            'description'=>'required',
            'amount'=>'required|numeric',

        ]);
        if($validator->fails())
            return response()->json(['success'=>'0','message'=>$validator->errors()->first()],400);

        // Get Prev Balance
        $prevBalanceTrans = Transactions::orderBy('created_at', 'desc')->first(['balance']);
        $prevBalance = $prevBalanceTrans ? $prevBalanceTrans->balance : 0;

        $transaction = new Transactions;
        $transaction->type = $request->input('type');
        $transaction->description = $request->input('description');
        $transaction->amount = $request->input('amount');
        $transaction->balance = $request->input('type') == 'credit'
            ? $prevBalance + $request->input('amount')
            : $prevBalance - $request->input('amount');


        if ($transaction->save()){
            return response()->json(['success' => 1, 'message' => 'success', 'data' => $transaction], 200);
        } else {
            return response()->json(['success' => 0, 'message' => 'error'], 400);
        }
    }
}
