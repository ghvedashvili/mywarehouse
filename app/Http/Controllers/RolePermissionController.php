<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $roles = RolePermission::roles();
        $pages = RolePermission::pages();

        $permissions = [];
        foreach (array_keys($roles) as $role) {
            $perms = RolePermission::where('role', $role)->get()->keyBy('page');
            foreach (array_keys($pages) as $page) {
                $permissions[$role][$page] = $perms->get($page) ?? (object)[
                    'can_view'   => false,
                    'can_edit'   => false,
                    'can_create' => false,
                ];
            }
        }

        return view('roles.index', compact('roles', 'pages', 'permissions'));
    }

    public function update(Request $request, string $role)
    {
        if (!array_key_exists($role, RolePermission::roles())) {
            abort(404);
        }

        $pages = array_keys(RolePermission::pages());

        foreach ($pages as $page) {
            $canView   = (bool) ($request->input("permissions.{$page}.can_view",   false));
            $canEdit   = (bool) ($request->input("permissions.{$page}.can_edit",   false));
            $canCreate = (bool) ($request->input("permissions.{$page}.can_create", false));

            RolePermission::updateOrCreate(
                ['role' => $role, 'page' => $page],
                ['can_view' => $canView, 'can_edit' => $canEdit, 'can_create' => $canCreate]
            );
        }

        RolePermission::clearCache($role);

        return response()->json(['success' => true]);
    }
}
