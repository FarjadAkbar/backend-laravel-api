<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Config;
use App\Models\User;

class UsersController extends Controller
{
    //
    public function index(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response(['message' => ['These credentials do not match our records.']], 404);
        }

        if (!$user->hasVerifiedEmail()) {
            return response(['message' => ['Please verify your email first.']], 403);
        }

        $remember = $request->input('remember', false);

        if ($remember) {
            Auth::login($user, true); // remember the user for 5 years
        } else {
            Auth::login($user); // don't remember the user
        }

        $token = $user->createToken('my-app-token')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
        ];

        return response($response, 201);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    'message' => $validator->errors()->first(),
                ],
                422,
            );
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        $user->sendEmailVerificationNotification();

        return response()->json(
            [
                'message' => 'Registration successful. Please verify your email.',
            ],
            201,
        );
    }

    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json($user);
    }


    public function update(Request $request)
    {
        $user = auth()->user();

        // Check if email has changed
        if ($request->has('email') && $request->email != $user->email) {
            $user->email = $request->email;
            $user->email_verified_at = null;

            // Generate email verification link for the new email
            $verificationUrl = URL::temporarySignedRoute('verification.verify', Carbon::now()->addMinutes(Config::get('auth.verification.expire', 600)), ['id' => $user->id, 'hash' => sha1($user->email)]);

            // Send confirmation email to the customer
            $user->sendEmailVerificationNotification();

            // Send success email to new email
            Mail::to($request->email)->send(new PasswordChanged($verificationUrl));
        }

        // Check if password has changed
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);

            // Send confirmation email to the customer
            $user->sendEmailVerificationNotification();
        }

        // Update name
        if ($request->has('name')) {
            $user->name = $request->name;
        }

        $user->save();
        return response(['message' => 'User updated successfully.'], 200);
    }

    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response(['message' => ['The provided verification link is invalid.']], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response(['message' => ['Your email address has already been verified.']], 409);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response(['message' => ['Your email has been verified.']]);
    }

    public function reset(){
        return response(['message' => ['Enter new password here.']]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 400);
    }


    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, $password) {
            $user
                ->forceFill([
                    'password' => Hash::make($password),
                ])
                ->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)]);
        }

        return response()->json(['message' => __($status)], 400);
    }

    public function logout(Request $request)
    {
        if (auth()->check()) {
            auth()
                ->user()
                ->tokens()
                ->delete();
            return response()->json(['message' => 'User logged out successfully']);
        } else {
            return response()->json(['message' => 'No user is currently logged in']);
        }
    }
}
