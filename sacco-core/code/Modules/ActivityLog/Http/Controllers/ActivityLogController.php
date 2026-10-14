<?php

namespace Modules\ActivityLog\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:activitylog.activity_logs.index'])->only(['index', 'show', 'get_activity_logs', 'export']);
        $this->middleware(['permission:activitylog.activity_logs.destroy'])->only(['destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?: 25;
        $userId = $request->user_id;
        $actionType = $request->action_type;
        $module = $request->module;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $logName = $request->log_name;
        $search = $request->s;

        $query = $this->buildQuery($userId, $actionType, $module, $dateFrom, $dateTo, $logName, $search);

        $data = $query->orderByDesc('activity_log.created_at')
            ->paginate($perPage)
            ->appends($request->input());

        // Stats
        $today = \Carbon\Carbon::today();
        $weekStart = \Carbon\Carbon::today()->startOfWeek();
        $stats = [
            'today_events'       => Activity::whereDate('created_at', $today)->count(),
            'week_events'        => Activity::where('created_at', '>=', $weekStart)->count(),
            'unique_users_today' => Activity::whereDate('created_at', $today)->whereNotNull('causer_id')->distinct('causer_id')->count('causer_id'),
            'failed_logins_today'=> Activity::whereDate('created_at', $today)->where('description', 'Failed Login Attempt')->count(),
        ];

        // Filter options
        $users = \DB::table('users')
            ->whereIn('id', Activity::whereNotNull('causer_id')->distinct('causer_id')->pluck('causer_id'))
            ->select('id', 'first_name', 'last_name')
            ->get();

        $modules = Activity::whereNotNull('module')->distinct('module')->pluck('module')->filter()->values();
        $actionTypes = ['create', 'update', 'delete', 'login', 'logout', 'failed', 'view', 'export', 'approve', 'reject', 'transfer'];

        return theme_view('activitylog::activity_log.index', compact('data', 'stats', 'users', 'modules', 'actionTypes'));
    }

    protected function buildQuery($userId, $actionType, $module, $dateFrom, $dateTo, $logName, $search): Builder
    {
        return Activity::leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->selectRaw("activity_log.*, CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as user_name")
            ->when($userId, function ($q) use ($userId) {
                $q->where('activity_log.causer_id', $userId);
            })
            ->when($actionType, function ($q) use ($actionType) {
                $q->where('activity_log.action_type', $actionType);
            })
            ->when($module, function ($q) use ($module) {
                $q->where('activity_log.module', $module);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('activity_log.created_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('activity_log.created_at', '<=', $dateTo);
            })
            ->when($logName, function ($q) use ($logName) {
                $q->where('activity_log.log_name', $logName);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('activity_log.description', 'like', "%$search%")
                        ->orWhere('users.first_name', 'like', "%$search%")
                        ->orWhere('users.last_name', 'like', "%$search%")
                        ->orWhere('activity_log.ip_address', 'like', "%$search%");
                });
            });
    }

    public function get_activity_logs(Request $request)
    {
        $causer_id = $request->causer_id;
        $query = Activity::leftJoin('users', 'users.id', '=', 'activity_log.causer_id')
            ->when($causer_id, function ($query) use ($causer_id) {
                $query->where('activity_log.causer_id', $causer_id);
            })
            ->selectRaw("activity_log.*, CONCAT(COALESCE(users.first_name,''), ' ', COALESCE(users.last_name,'')) as user_name");

        return DataTables::of($query)->editColumn('action', function ($data) {
            return '<a href="' . url('activity_log/' . $data->id . '/show') . '" class=""><i class="fa fa-eye"></i></a>';
        })->editColumn('user_name', function ($data) {
            return '<a href="' . url('user/' . $data->causer_id . '/show') . '">' . $data->user_name . '</a>';
        })->editColumn('created_at', function ($data) {
            return $data->created_at->format('Y-m-d H:i:s');
        })->editColumn('id', function ($data) {
            return '<a href="' . url('activity_log/' . $data->id . '/show') . '">' . $data->id . '</a>';
        })->rawColumns(['id', 'action', 'user_name'])->make(true);
    }

    public function show($id)
    {
        $activity_log = Activity::with('causer')->findOrFail($id);

        // Compute before/after diff if properties contain old+attributes keys (spatie dirty logging)
        $diff = [];
        $props = $activity_log->properties;
        if ($props && $props->has('old') && $props->has('attributes')) {
            $old = $props->get('old');
            $new = $props->get('attributes');
            $allKeys = array_unique(array_merge(array_keys((array) $old), array_keys((array) $new)));
            foreach ($allKeys as $key) {
                $oldVal = isset($old[$key]) ? $old[$key] : null;
                $newVal = isset($new[$key]) ? $new[$key] : null;
                $diff[$key] = ['old' => $oldVal, 'new' => $newVal, 'changed' => $oldVal !== $newVal];
            }
        }

        return theme_view('activitylog::activity_log.show', compact('activity_log', 'diff'));
    }

    /**
     * Export filtered audit logs as CSV.
     */
    public function export(Request $request)
    {
        $userId = $request->user_id;
        $actionType = $request->action_type;
        $module = $request->module;
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        $logName = $request->log_name;
        $search = $request->s;

        $rows = $this->buildQuery($userId, $actionType, $module, $dateFrom, $dateTo, $logName, $search)
            ->orderByDesc('activity_log.created_at')
            ->limit(10000)
            ->get();

        $filename = 'audit_log_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'User', 'Action Type', 'Description', 'Module', 'Log Name', 'Subject Type', 'Subject ID', 'IP Address', 'Date/Time']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    trim($row->user_name),
                    $row->action_type,
                    $row->description,
                    $row->module,
                    $row->log_name,
                    $row->subject_type,
                    $row->subject_id,
                    $row->ip_address,
                    $row->created_at,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
