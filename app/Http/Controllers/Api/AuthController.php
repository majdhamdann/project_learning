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
            
        ]);
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }
    

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);
    
        $user = User::where('phone', $credentials['phone'])->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'رقم الهاتف غير مسجل.',
            ], 404);
        }
    
       
        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة.',
            ], 401);
        }
    
      
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'تم تسجيل الدخول بنجاح.',
            'user' => $user,
            'token' => $token,
        ]);
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Successfully logged out']);
    }


    public function updateUserImage(Request $request)
    {
        $request->validate([
            'user_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);
    
        $teacherId = auth()->id();
    
        
        $teacher = User::findOrFail($teacherId);
    
       
        if ($teacher->teacher_image) {
            $oldPath = public_path(parse_url($teacher->teacher_image, PHP_URL_PATH));
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    
       
        $file = $request->file('user_image');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('uploads/user_profiles'), $fileName);
    
        
        $imagePath = url('uploads/user_profiles/' . $fileName);
        $teacher->update(['user_image' => $imagePath]);
    
        return response()->json([
            'message' => 'User image updated successfully',
            'user_image' => $imagePath
        ]);
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

