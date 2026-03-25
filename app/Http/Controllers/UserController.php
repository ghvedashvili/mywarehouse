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
        // index, apiUsers, changePasswordForm, changePassword — ყველა auth-ულ იუზერს
        // create/store/edit/update/destroy — მხოლოდ admin და staff-ს
        $this->middleware('auth');
        $this->middleware('role:admin,staff')->except([
            'changePasswordForm',
            'changePassword',
        ]);
    }

    // ─── CRUD ────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('user.index');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required',
            'email' => 'required|unique:users',
        ]);

        User::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'User Created',
        ]);
    }

    public function show($id)
    {
        //
    }

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

        return response()->json([
            'success' => true,
            'message' => 'User Updated',
        ]);
    }

    public function destroy($id)
    {
        // საკუთარი თავის წაშლა არ შეიძლება
        if (Auth::id() == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete your own account',
            ], 403);
        }

        User::destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'User Deleted',
        ]);
    }

    public function apiUsers()
    {
        $users = User::all();

        return DataTables::of($users)
            ->addColumn('action', function ($user) {
                return '<a onclick="editForm(' . $user->id . ')" class="btn btn-primary btn-xs">
                            <i class="glyphicon glyphicon-edit"></i> Edit
                        </a> ' .
                       '<a onclick="deleteData(' . $user->id . ')" class="btn btn-danger btn-xs">
                            <i class="glyphicon glyphicon-trash"></i> Delete
                        </a>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    // ─── PASSWORD CHANGE (ყველა auth-ული იუზერისთვის) ────────────────────────

    /**
     * პაროლის შეცვლის ფორმა.
     * Route: GET /user/change-password
     */
    public function changePasswordForm()
    {
        return view('user.change_password');
    }

    /**
     * პაროლის შეცვლა.
     * Route: POST /user/change-password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'      => ['required'],
            'password'              => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = Auth::user();

        // მიმდინარე პაროლის შემოწმება
        if (!Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'მიმდინარე პაროლი არასწორია.'])
                ->withInput();
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'პაროლი წარმატებით შეიცვალა!');
    }
}