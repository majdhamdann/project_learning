<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'phone'    => 'required|unique:users,phone',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
    
        $user = User::create([
            'name'     => $request->name,
            'phone'    => $request->phone,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => 1,
            'status' =>'accepted',
        ]);
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }
    
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }


    public function updateImage(Request $request)
{
    $request->validate([
        'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', 
    ]);

    $user = Auth::user();


    if ($request->hasFile('profile_image')) {
        $path = $request->file('profile_image')->store('profile_images', 'public');

        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        $user->profile_image = $path;
    }

   
    $user->save();
 
    return response()->json(['message' => 'User updated successfully', 'user' => $user]);
}

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
        ]);
        
        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json(['message' => 'Old password is incorrect'], 400);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
    
        return response()->json(['message' => 'Password upated successfully']);
    }
}

