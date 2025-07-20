<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\User; 
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'], 
            
            [
                'name' => 'Administrator',
                'phone' =>'0996644362',
                'email' => 'admin@example.com',
                'password' => Hash::make('12345678'), 
                'role_id' => '3', 
                          
           
                ]
        );
    }
}
