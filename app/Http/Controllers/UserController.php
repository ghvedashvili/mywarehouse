<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\DataTables;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        // მხოლოდ admin — წაშლა და როლის შეცვლა
       $this->middleware('role:admin')->only([
    'index',
    'destroy',
    'updateRole',
]);

        // admin და staff — დანარჩენი CRUD (index, store, edit, update)
        $this->middleware('role:admin,staff')->except([
            'changePasswordForm',
            'changePassword',
            'destroy',
            'updateRole',
        ]);
    }

    public function index()
    {
        return view('user.index');
    }

    public function create() {}

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required',
            'email' => 'required|unique:users',
        ]);

        User::create($request->all());

        return response()->json(['success' => true, 'message' => 'User Created']);
    }

    public function show($id) {}

    public function edit($id)
    {
        return User::find($id);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'  => 'required|string|min:2',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
        ]);

        $user = User::findOrFail($id);
        $user->update($request->only('name', 'email'));

        return response()->json(['success' => true, 'message' => 'User Updated']);
    }

    public function destroy($id)
    {
        if (Auth::id() == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        User::destroy($id);

        return response()->json(['success' => true, 'message' => 'User Deleted']);
    }

    public function apiUsers()
    {
        $users      = User::all();
        $isAdmin    = Auth::user()->role === 'admin';

        return DataTables::of($users)
            ->addColumn('action', function ($user) use ($isAdmin) {
                $btn = '<a onclick="editForm(' . $user->id . ')" class="btn btn-primary btn-xs">
                            <i class="glyphicon glyphicon-edit"></i> Edit
                        </a> ';

                if ($isAdmin) {
                    $btn .= '<a onclick="deleteData(' . $user->id . ')" class="btn btn-danger btn-xs">
                                <i class="glyphicon glyphicon-trash"></i> Delete
                             </a>';
                }

                return $btn;
            })
            ->addColumn('role', function ($user) use ($isAdmin) {
                $badge  = $user->role === 'admin' ? 'danger' : 'primary';
                $click  = $isAdmin ? 'onclick="changeRole(' . $user->id . ', \'' . $user->role . '\')"' : '';
                $cursor = $isAdmin ? 'cursor:pointer;' : 'cursor:default;';
                $title  = $isAdmin ? 'title="კლიკი როლის შესაცვლელად"' : '';

                return '<span ' . $click . ' ' . $title . '
                            class="badge badge-' . $badge . '"
                            style="font-size:12px; padding:5px 10px; ' . $cursor . '">
                            ' . strtoupper($user->role) . '
                        </span>';
            })
            ->rawColumns(['action', 'role'])
            ->make(true);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:admin,staff',
        ]);

        if (Auth::id() == $id) {
            return response()->json([
                'success' => false,
                'message' => 'საკუთარი როლის შეცვლა არ შეიძლება.',
            ], 403);
        }

        $user = User::findOrFail($id);
        $user->update(['role' => $request->role]);

        return response()->json([
            'success' => true,
            'message' => '"' . $user->name . '"-ის როლი შეიცვალა: ' . strtoupper($request->role),
        ]);
    }

    public function changePasswordForm()
    {
        return view('user.change_password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'მიმდინარე პაროლი არასწორია.'])
                ->withInput();
        }

        $user->update(['password' => Hash::make($request->password)]);

        return back()->with('success', 'პაროლი წარმატებით შეიცვალა!');
    }
}