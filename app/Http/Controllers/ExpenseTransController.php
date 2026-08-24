<?php

namespace App\Http\Controllers;

use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Throwable;
use Carbon\Carbon;
use App\Repositories\Expense\ExpenseInterface;
use App\Repositories\ExpenseCategory\ExpenseCategoryInterface;
use App\Repositories\SessionYear\SessionYearInterface;
use App\Services\CachingService;
use App\Services\SessionYearsTrackingsService;
use App\Models\ManageExpense;
use App\Models\ExpanceTransLog;
use App\Models\User;
use App\Models\Expense;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ExpenseExport;
class ExpenseTransController extends Controller
{
    private ExpenseInterface $expense;
    private ExpenseCategoryInterface $expenseCategory;
    private SessionYearInterface $sessionYear;
    private CachingService $cache;
    private SessionYearsTrackingsService $sessionYearsTrackingsService;

    public function __construct(
        ExpenseInterface $expense,
        ExpenseCategoryInterface $expenseCategory,
        SessionYearInterface $sessionYear,
        CachingService $cache,
        SessionYearsTrackingsService $sessionYearsTrackingsService
    ) {
        $this->expense = $expense;
        $this->expenseCategory = $expenseCategory;
        $this->sessionYear = $sessionYear;
        $this->cache = $cache;
        $this->sessionYearsTrackingsService = $sessionYearsTrackingsService;
    }

    // 🧾 Dashboard Summary
    public function index()
    {
        ResponseService::noFeatureThenRedirect('Expense Management');
        ResponseService::noAnyPermissionThenRedirect(['expense-create', 'expense-list']);

        $today = Carbon::today()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $expenseCategory = $this->expenseCategory->builder()->pluck('name', 'id')->toArray();

        $openingBalance = DB::table('manage_expenses')
            ->whereDate('created_at', $yesterday)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0)
                -
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0)
                AS closing_balance
            ")
            ->value('closing_balance');

        $todayCredits = DB::table('manage_expenses')->whereDate('created_at', $today)->where('type', 'credit')->sum('amount');
        $todayDebits = DB::table('manage_expenses')->whereDate('created_at', $today)->where('type', 'debit')->sum('amount');

        $todayTransactions = DB::table('manage_expenses')
            ->whereDate('created_at', $today)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = $openingBalance;

        foreach ($todayTransactions as $tx) {
            $runningBalance += ($tx->type === 'credit') ? $tx->amount : -$tx->amount;
            $tx->remaining_balance = $runningBalance;
        }

        $closingBalance = $runningBalance;
        $sessionYear = $this->sessionYear->builder()->pluck('name', 'id');
        $current_session_year = app(CachingService::class)->getDefaultSessionYear();
        $months = sessionYearWiseMonth();

        return view('expense-trans.index', compact('openingBalance', 'closingBalance', 'todayCredits', 'todayDebits', 'expenseCategory', 'sessionYear', 'current_session_year', 'months'));
    }

    // 🧮 Summary (AJAX)
    public function getExpenseSummary(Request $request)
    {
        $date = $request->date ?? Carbon::today()->toDateString();
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        $openingBalance = DB::table('manage_expenses')
            ->whereDate('created_at', '<=', $yesterday)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END), 0)
                -
                COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END), 0)
                AS closing_balance
            ")
            ->value('closing_balance') ?? 0;

        $todayCredits = DB::table('manage_expenses')->whereDate('created_at', $date)->where('type', 'credit')->sum('amount');
        $todayDebits = DB::table('manage_expenses')->whereDate('created_at', $date)->where('type', 'debit')->sum('amount');

        $todayTransactions = ManageExpense::whereDate('created_at', $date)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $runningBalance = $openingBalance;
        foreach ($todayTransactions as $tx) {
            $runningBalance += ($tx->type === 'credit') ? $tx->amount : -$tx->amount;
            $tx->remaining_balance = $runningBalance;
        }

        return response()->json([
            'status' => true,
            'openingBalance' => $openingBalance,
            'closingBalance' => $runningBalance,
            'todayCredits' => $todayCredits,
            'todayDebits' => $todayDebits,
            'transactions' => $todayTransactions,
        ]);
    }

    // 💾 Create new expense
    public function store(Request $request)
    {
        ResponseService::noFeatureThenSendJson('Expense Management');
        ResponseService::noPermissionThenSendJson('expense-create');

        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'date' => 'nullable|date',
        ]);

        try {
            DB::beginTransaction();

            $latestBalance = ManageExpense::orderBy('id', 'desc')->value('balance') ?? 0;
            $newBalance = $latestBalance + $request->amount;

            $trans = ManageExpense::create([
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'type' => 'credit',
                'balance' => $newBalance,
                'transaction_date' => $request->date ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // ExpanceTransLog::create([
            //     'expnace_trans_id' => $trans->id,
            //     'user_id' => auth()->id(),
            //     'trans_type' => 'create',
            //     'amount' => $request->amount,
            //     'description' => 'Created: ' . $request->description,
            //     'created_at' => now(),
            //     'updated_at' => now(),
            // ]);

            DB::commit();
            ResponseService::successResponse('Amount added successfully');
        } catch (Throwable $e) {
            DB::rollBack();
            ResponseService::logErrorResponse($e, "ExpenseTransController -> store()");
            ResponseService::errorResponse();
        }
    }

    // ✏️ Update expense
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $trans = ManageExpense::findOrFail($id);

            ExpanceTransLog::create([
                'expnace_trans_id' => $trans->id,
                'user_id' => auth()->id(),
                'trans_type' => 'update',
                'amount' => $request->amount,
                'description' => 'Updated: ' . $request->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Expense::where('id', $trans->exp_id)->update(['amount' => $request->amount]);

            $trans->update([
                'title' => $request->title,
                'amount' => $request->amount,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // 🗑️ Delete expense (with log)
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $trans = ManageExpense::find($id);

            if (!$trans) {
                return response()->json(['success' => false, 'error' => 'Record not found.']);
            }

            ExpanceTransLog::create([
                'expnace_trans_id' => $trans->id,
                'user_id' => auth()->id(),
                'trans_type' => 'delete',
                'amount' => $trans->amount,
                'description' => 'Deleted: ' . $trans->description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Expense::where('id', $trans->exp_id)->delete();
            $trans->delete();

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Expense deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    // 📋 List summary with logs
    // public function todaySummary(Request $request)
    // {
    //     $offset = $request->get('offset', 0);
    //     $limit = $request->get('limit', 10);
    //     $query = ManageExpense::orderBy('created_at', 'desc');
    //     if ($request->from_date) {
    //         $query->whereDate('transaction_date', $request->from_date);
    //     }
    //     $total = $query->count();

    //     $rows = $query->skip($offset)->take($limit)->get()->map(function ($item, $index) use ($offset) {
    //         $logs = ExpanceTransLog::where('expnace_trans_id', $item->id)
    //             ->orderBy('created_at', 'desc')
    //             ->get()
    //             ->map(function ($log) {
    //                 return [
    //                     'amount' => $log->amount,
    //                     'description' => $log->description,
    //                     'updated_by_name' => $log->user ? ($log->user->first_name . ' ' . $log->user->last_name) : null,
    //                     'updated_at' => Carbon::parse($log->created_at)->format('d-m-Y H:i'),
    //                 ];
    //             });

    //         return [
    //             'no' => $offset + $index + 1,
    //             'id' => $item->id,
    //             'title' => $item->title,
    //             'type' => ucfirst($item->type),
    //             'amount' => number_format($item->amount, 2),
    //             'balance' => number_format($item->balance, 2),
    //             'transaction_date' => Carbon::parse($item->transaction_date)->format('d-m-Y'),
    //             'logs' => $logs,
    //         ];
    //     });

    //     return response()->json([
    //         'total' => $total,
    //         'rows' => $rows
    //     ]);
    // }
public function todaySummary(Request $request)
{
    $offset = $request->get('offset', 0);
    $limit  = $request->get('limit', 10);
    $query = ManageExpense::orderBy('created_at', 'asc')
        ->with('expense.category'); // ✅ eager load

    if ($request->from_date) {
        $query->whereDate('transaction_date', '>=', $request->from_date);
    }

    if ($request->to_date) {
        $query->whereDate('transaction_date', '<=', $request->to_date);
    }

    if ($request->category_id) {
        $query->whereHas('expense', function ($q) use ($request) {
            $q->where('category_id', $request->category_id);
        });
    }

    $total = $query->count();

    $totalDebit  = (clone $query)->where('type', 'debit')->sum('amount');
    $totalCredit = (clone $query)->where('type', 'credit')->sum('amount');

    $rows = $query->skip($offset)->take($limit)->get()
        ->map(function ($item, $index) use ($offset) {

            $logs = ExpanceTransLog::where('expnace_trans_id', $item->id)
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($log) {
                    return [
                        'amount' => number_format($log->amount, 2),
                        'description' => $log->description,
                        'updated_by_name' => $log->user
                            ? $log->user->first_name . ' ' . $log->user->last_name
                            : null,
                        'updated_at' => Carbon::parse($log->created_at)->format('d-m-Y H:i'),
                    ];
                });

            return [
                'no' => $offset + $index + 1,
                'id' => $item->id,
                'title' => $item->title,
                

                // ✅ CATEGORY NAME (NO JOIN)
                'category' => optional(optional($item->expense)->category)->name,


                'debit'  => $item->type === 'debit'
                    ? number_format($item->amount, 2)
                    : '',

                'credit' => $item->type === 'credit'
                    ? number_format($item->amount, 2)
                    : '',
                'amount' => $item->amount,
                'balance' => number_format($item->balance, 2),
                'transaction_date' => Carbon::parse($item->transaction_date)->format('d-m-Y'),
                'logs' => $logs,
            ];
        });

    return response()->json([
        'total' => $total,
        'total_debit'  => number_format($totalDebit, 2),
        'total_credit' => number_format($totalCredit, 2),
        'rows' => $rows,
        'limit'=> $limit
    ]);
}
    public function export(Request $request)
    {
        // 🔹 SAME BASE QUERY AS todaySummary()
        $query = ManageExpense::orderBy('created_at', 'asc')
            ->with('expense.category');

        if ($request->from_date) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        if ($request->category_id) {
            $query->whereHas('expense', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // 🔹 TOTALS (IMPORTANT)
        $totalDebit  = (clone $query)->where('type', 'debit')->sum('amount');
        $totalCredit = (clone $query)->where('type', 'credit')->sum('amount');

        // 🔹 ALL DATA (NO OFFSET / LIMIT)
        $data = $query->get()->map(function ($item, $index) {
            return [
                'no' => $index + 1,
                'title' => $item->title,
                'category' => optional(optional($item->expense)->category)->name,
                'debit' => $item->type === 'debit'
                    ? number_format($item->amount, 2)
                    : '',
                'credit' => $item->type === 'credit'
                    ? number_format($item->amount, 2)
                    : '',
                'transaction_date' => Carbon::parse($item->transaction_date)->format('d-m-Y'),
            ];
        });

        return Excel::download(
            new ExpenseExport($data, $totalDebit, $totalCredit),
            'transactions-' . date('d-m-y') . '.xlsx'
        );
    }


}
