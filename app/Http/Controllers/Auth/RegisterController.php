<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | მხოლოდ admin და staff-ს შეუძლია ახალი იუზერის დამატება.
    | რეგისტრაციის შემდეგ ახალი იუზერით არ ხდება ავტო-ლოგინი.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     * (გამოიყენება მხოლოდ თუ registered() არ არის override-ული)
     */
    protected $redirectTo = '/user';

    public function __construct()
    {
        $this->middleware('role:admin,staff');
    }

    /**
     * Validator for incoming registration request.
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     */
    protected function create(array $data)
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Override: რეგისტრაციის შემდეგ ახალი იუზერით არ ვლოგინდებით.
     * trait-ის სტანდარტული registered() მეთოდი guard()->login($user) ს იძახებს —
     * აქ მას ვაუქმებთ და უბრალოდ /user-ზე გადავდივართ.
     */
    protected function registered(Request $request, $user)
    {
        return redirect($this->redirectTo)
            ->with('success', 'იუზერი "' . $user->name . '" წარმატებით დაემატა!');
    }
}