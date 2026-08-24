<?php

namespace App\Http\Controllers;

use App\Models\ManualUpiTransaction;
use Illuminate\Http\Request;

class ManualUpiTransactionController extends Controller
{
    public function index()
    {
        $request = request();
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $query = ManualUpiTransaction::with('student.user')->latest();

        if ($request->has('date') && $request->date != '') {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->has('name') && $request->name != '') {
            $query->whereHas('student.user', function ($q) use ($request) {
                $q->where('first_name', 'like', '%' . $request->name . '%')
                  ->orWhere('last_name', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->export === 'csv') {
            $transactions = $query->get();
            $csvData = "ID,Student Name,Amount,Transaction ID,Status,Date\n";
            foreach ($transactions as $t) {
                $name = ($t->student->user->first_name ?? '') . ' ' . ($t->student->user->last_name ?? '');
                $csvData .= "{$t->id},\"{$name}\",{$t->amount},{$t->transaction_id},{$t->status},{$t->created_at}\n";
            }
            return response($csvData)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="manual_upi_transactions.csv"');
        }

        if ($request->export === 'pdf') {
            $transactions = $query->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('manual_upi_transactions.pdf', compact('transactions'));
            return $pdf->download('manual_upi_transactions.pdf');
        }

        $transactions = $query->paginate(15)->appends($request->all());
        return view('manual_upi_transactions.index', compact('transactions'));
    }

    public function accept($id)
    {
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction = ManualUpiTransaction::findOrFail($id);
        $transaction->status = 'success';
        $transaction->save();

        return redirect()->back()->with('success', __('Transaction accepted successfully.'));
    }

    public function reject($id)
    {
        if (!auth()->user()->can('manual-upi-transaction-list')) {
            abort(403, 'Unauthorized action.');
        }

        $transaction = ManualUpiTransaction::findOrFail($id);
        $transaction->status = 'failed';
        $transaction->save();

        return redirect()->back()->with('success', __('Transaction rejected successfully.'));
    }
}
