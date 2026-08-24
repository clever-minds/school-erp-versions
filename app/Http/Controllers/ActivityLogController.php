<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Config;

class ActivityLogController extends Controller
{
    /**
     * Show the Activity Logs page.
     */
    public function index()
    {
        $databases = DB::table('schools')->select('database_name', 'name')->get();
        return view('activity_logs.index', compact('databases'));
    }

    /**
     * Fetch logs dynamically via AJAX.
     */
//   public function fetchLogs(Request $request)
// {
//     $request->validate([
//         'database_name' => 'required|string',
//         'page' => 'nullable|integer|min:1',
//         'limit' => 'nullable|integer|min:1|max:100',
//     ]);

//     $databaseName = $request->database_name;
//     $page = (int) $request->input('page', 1);
//     $limit = (int) $request->input('limit', 10);
//     $offset = ($page - 1) * $limit;

//     try {
//         // ✅ Dynamically connect to selected school DB
//         \Config::set('database.connections.dynamic', [
//             'driver' => 'mysql',
//             'host' => env('DB_HOST', '127.0.0.1'),
//             'port' => env('DB_PORT', '3306'),
//             'database' => $databaseName,
//             'username' => env('DB_USERNAME', 'root'),
//             'password' => env('DB_PASSWORD', ''),
//             'charset' => 'utf8mb4',
//             'collation' => 'utf8mb4_unicode_ci',
//         ]);

//         DB::purge('dynamic');
//         DB::reconnect('dynamic');

//         $total = DB::connection('dynamic')->table('activity_logs')->count();

//          $logs = DB::connection('dynamic')
//                 ->table('activity_logs as a')
//                 ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
//                 ->select(
//                     'a.id',
//                     'a.user_id',
//                      DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name"),
//                     'a.model_name',
//                     'a.action',
//                     'a.record_id',
//                     'a.changes',
//                     'a.created_at'
//                 )
//                 ->orderBy('a.id', 'desc')
//                 ->offset($offset)
//                 ->limit($limit)
//                 ->get();


//         $logs = $logs->map(function ($log) {
           

//             return [
//                 'id' => $log->id,
//                 'user_id' => $log->user_id,
//                 'user_name' => $log->user_name,
//                 'model_name' => $log->model_name,
//                 'action' => $log->action,
//                 'record_id' => $log->record_id,
//                 'changes' =>  $log->changes,
//                 'created_at' => $log->created_at,
//             ];
//         });

//         // ✅ Return paginated JSON
//         return response()->json([
//             'status' => true,
//             'data' => $logs,
//             'pagination' => [
//                 'total' => $total,
//                 'page' => $page,
//                 'limit' => $limit,
//                 'pages' => ceil($total / $limit),
//             ]
//         ]);
//     } catch (\Exception $e) {
//         return response()->json([
//             'status' => false,
//             'message' => $e->getMessage(),
//         ], 500);
//     }
// }
public function fetchLogs(Request $request)
{
    $request->validate([
        'database_name' => 'required|string',
        'limit' => 'nullable|integer|min:1|max:200',
        'offset' => 'nullable|integer|min:0',
        'order' => 'nullable|string|in:asc,desc',
        'sort' => 'nullable|string',
        'search' => 'nullable|string'
    ]);

    $databaseName = $request->input('database_name');
    $limit = (int) $request->input('limit', 10);
    $offset = (int) $request->input('offset', 0);
    $order = $request->input('order', 'desc');
    $sort = $request->input('sort', 'id');
    $search = trim($request->input('search', ''));

    try {
        // ✅ Dynamically connect to selected database
        \Config::set('database.connections.dynamic', [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        DB::purge('dynamic');
        DB::reconnect('dynamic');

        // ✅ Base query
        $query = DB::connection('dynamic')
            ->table('activity_logs as a')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.id')
            ->select(
                'a.id',
                'a.user_id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name"),
                'a.model_name',
                'a.action',
                'a.record_id',
                'a.changes',
                'a.created_at'
            );

        // ✅ Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('a.model_name', 'like', "%$search%")
                  ->orWhere('a.action', 'like', "%$search%")
                  ->orWhere('a.record_id', 'like', "%$search%")
                  ->orWhere('a.changes', 'like', "%$search%")
                  ->orWhere(DB::raw("CONCAT(u.first_name, ' ', u.last_name)"), 'like', "%$search%");
            });
        }

        // ✅ Count before pagination
        $total = $query->count();

        // ✅ Apply sorting and pagination
        $logs = $query->orderBy("a.$sort", $order)
                      ->offset($offset)
                      ->limit($limit)
                      ->get();

        // ✅ Format response data
        $rows = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'user_id' => $log->user_id,
                'user_name' => $log->user_name ?? 'N/A',
                'model_name' => $log->model_name,
                'action' => ucfirst($log->action),
                'record_id' => $log->record_id,
                'changes' => $log->changes,
                'created_at' => \Carbon\Carbon::parse($log->created_at)->format('d M Y, h:i A'),
            ];
        });

        // ✅ Return JSON formatted for Bootstrap Table
        return response()->json([
            'total' => $total,
            'rows' => $rows
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'total' => 0,
            'rows' => [],
            'error' => $e->getMessage(),
        ], 500);
    }
}


}
