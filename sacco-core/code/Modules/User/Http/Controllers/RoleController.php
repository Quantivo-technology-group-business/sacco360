<?php

namespace Modules\User\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Laracasts\Flash\Flash;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:user.roles.index'])->only(['index', 'show', 'get_roles']);
        $this->middleware(['permission:user.roles.create'])->only(['create', 'store']);
        $this->middleware(['permission:user.roles.edit'])->only(['edit', 'update']);
        $this->middleware(['permission:user.roles.destroy'])->only(['destroy']);

    }

    public function index(Request $request)
    {
        $perPage = $request->per_page ?: 20;
        $orderBy = $request->order_by;
        $orderByDir = $request->order_by_dir;
        $search = $request->s;
        $data = Role::when($orderBy, function (Builder $query) use ($orderBy, $orderByDir) {
            $query->orderBy($orderBy, $orderByDir);
        })
            ->when($search, function (Builder $query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->paginate($perPage)
            ->appends($request->input());
        return theme_view('user::role.index', compact('data'));
    }

    public function get_roles(Request $request)
    {
        $query = Role::query();
        return DataTables::of($query)->editColumn('user', function ($data) {
            return $data->first_name . ' ' . $data->last_name;
        })->editColumn('action', function ($data) {
            $action = '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="true"><i class="fa fa-navicon"></i></button> <ul class="dropdown-menu dropdown-menu-right" role="menu">';
            $action .= '<li><a href="' . url('user/role/' . $data->id . '/show') . '" class="">' . trans_choice('user::general.detail', 2) . '</a></li>';
            $isSystemRole = $data->is_system == 1;
            $canEditSystem = Auth::user()->hasRole('admin');
            if (Auth::user()->hasPermissionTo('user.roles.edit') && (!$isSystemRole || $canEditSystem)) {
                $action .= '<li><a href="' . url('user/role/' . $data->id . '/edit') . '" class="">' . trans_choice('user::general.edit', 2) . '</a></li>';
            }
            if (Auth::user()->hasPermissionTo('user.roles.destroy') && !$isSystemRole) {
                $action .= '<li><a href="' . url('user/role/' . $data->id . '/destroy') . '" class="confirm">' . trans_choice('user::general.delete', 2) . '</a></li>';
            }
            $action .= "</ul></li></div>";
            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('user/role/' . $data->id . '/show') . '">' . $data->id . '</a>';

        })->editColumn('name', function ($data) {
            return '<a href="' . url('user/role/' . $data->id . '/show') . '">' . $data->name . '</a>';

        })->editColumn('is_system', function ($data) {
            if ($data->is_system == "1") {
                return trans_choice('core::general.yes', 1);
            }
            if ($data->is_system == "0") {
                return trans_choice('core::general.no', 1);
            }

        })->rawColumns(['id', 'name', 'action'])->make(true);
    }

    public function create()
    {
        $permissionTree = Permission::treeByModule();
        $roles = Role::all();
        $selected_permissions = [];
        return theme_view('user::role.create', compact('permissionTree', 'roles', 'selected_permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'parent_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        if ($request->parent_role_id && Role::wouldCreateCircularInheritance(0, (int) $request->parent_role_id)) {
            Flash::warning(trans_choice('user::general.invalid_role_inheritance', 1));
            return redirect()->back()->withInput();
        }

        $role = Role::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_role_id' => $request->parent_role_id ?: null,
        ]);

        $permissionIds = $request->permissions ?? [];
        if (!empty($permissionIds)) {
            $role->syncPermissions(Permission::whereIn('id', $permissionIds)->get());
        }

        activity()->on($role)
            ->withProperties(['id' => $role->id])
            ->log('Create Role');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        return redirect('user/role');
    }

    public function show($id)
    {
        $role = Role::findById($id);
        $permissionTree = Permission::treeByModule();
        $selected_permissions = $role->permissions->pluck('id')->all();
        $inherited_permissions = $role->getInheritedPermissions()->pluck('id')->all();
        $roles = Role::all();
        return theme_view('user::role.show', compact('role', 'permissionTree', 'selected_permissions', 'inherited_permissions', 'roles'));
    }

    public function edit($id)
    {
        $role = Role::findById($id);
        if ($role->is_system == 1 && !Auth::user()->hasRole('admin')) {
            Flash::warning(trans_choice("user::general.cannot_edit_system_role", 1));
            return redirect()->back();
        }
        $permissionTree = Permission::treeByModule();
        $selected_permissions = $role->permissions->pluck('id')->all();
        $roles = Role::where('id', '!=', $role->id)->get();
        return theme_view('user::role.edit', compact('role', 'permissionTree', 'selected_permissions', 'roles'));
    }

    public function update(Request $request, $id)
    {
        if (App::environment('demo')) {
            return redirect()->back()->with('error', 'Action not allowed in demo.');
        }
        $role = Role::findById($id);
        if ($role->is_system == 1 && !Auth::user()->hasRole('admin')) {
            Flash::warning(trans_choice("user::general.cannot_edit_system_role", 1));
            return redirect()->back();
        }
        $request->validate([
            'name' => ['required'],
            'parent_role_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        if (Role::wouldCreateCircularInheritance((int) $role->id, $request->parent_role_id ? (int) $request->parent_role_id : null)) {
            Flash::warning(trans_choice('user::general.invalid_role_inheritance', 1));
            return redirect()->back()->withInput();
        }

        $role->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_role_id' => $request->parent_role_id ?: null,
        ]);

        $permissionIds = $request->permissions ?? [];
        $role->syncPermissions(Permission::whereIn('id', $permissionIds)->get());

        activity()->on($role)
            ->withProperties(['id' => $role->id])
            ->log('Update Role');
        \flash(trans_choice("core::general.successfully_saved", 1))->success()->important();
        return redirect('user/role');
    }

    public function destroy($id)
    {
        if (App::environment('demo')) {
            return redirect()->back()->with('error', 'Action not allowed in demo.');
        }
        $role = Role::findById($id);
        if ($role->is_system == 1) {
            Flash::warning(trans_choice("user::general.cannot_delete_system_role", 1));
            return redirect()->back();
        }
        $role->delete();
        activity()->on($role)
            ->withProperties(['id' => $role->id])
            ->log('Delete Role');
        \flash(trans_choice("core::general.successfully_deleted", 1))->success()->important();
        return redirect()->back();
    }
}
