<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create(StoreUserRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password']),
                'role_id' => $validated['role_id'],
            ]);
    
            return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create user', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateUserRequest $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validated();

if ($request->has('name')) {
    $user->name = $request->input('name');
}
if ($request->has('phone')) {
    $user->email = $request->input('phone');
}
if ($request->has('role_id')) {
    $user->role_id = $request->input('role_id');
}
if ($request->has('password')) {
    $user->password = Hash::make($request->input('password'));
}

$user->save();

return response()->json(['message' => 'User updated successfully', 'user' => $user]);

        
}
    public function delete($id)
    {

      
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }
    
        $user->delete();
        return response()->json(['message' => 'User deleted successfully'], 200);

    }

    public function index()
    {
        $users = User::all();
        return response()->json(['users' => $users], 200);
    }
}
