<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function register(Request $request){
        $registerUserData = $request->validate([
            'full-name'=>'required|string',
            'phone'=>'required|unique:users',
            'password'=>'required|min:8',
        ]);
        $user = User::create([
            'name' => $registerUserData['full-name'],
            'phone' => $registerUserData['phone'],
            'password' => Hash::make($registerUserData['password']),
            'role_id' => 1,
        ]);
        
        return response()->json([
            'message' => 'User Created ',
        ]);
    }
    public function login(Request $request){
        $loginUserData = $request->validate([
            'phone'=>'required',
            'password'=>'required|min:8'
        ]);
        $user = User::where('phone',$loginUserData['phone'])->first();
        if(!$user || !Hash::check($loginUserData['password'],$user->password)){
            return response()->json([
                'message' => 'Invalid Credentials'
            ],401);
        }
        $token = $user->createToken($user->name.'-AuthToken')->plainTextToken;
        return response()->json([
            'access_token' => $token,
        ]);
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

