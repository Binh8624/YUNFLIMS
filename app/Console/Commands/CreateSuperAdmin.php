<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create-super-admin';
    protected $description = 'Create the first Super Admin account';

    public function handle(): int
    {
        $name = $this->ask('Full name');
        $email = $this->ask('Email');
        $password = $this->secret('Password');
        $confirmation = $this->secret('Confirm password');

        $passwordConfirmation = ['password_confirmation' => $confirmation];
        $input = ['name' => $name, 'email' => $email, 'password' => $password] + $passwordConfirmation;
        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        if (User::whereIn('role', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])->exists()) {
            $this->error('A Super Admin or Admin account already exists.');
            return self::FAILURE;
        }

        $user = new User;
        $user->name = $name;
        $user->email = strtolower(trim($email));
        $user->password = Hash::make($password);
        $user->role = User::ROLE_SUPER_ADMIN;
        $user->is_active = true;
        $user->save();

        $this->info('Super Admin account created successfully.');
        return self::SUCCESS;
    }
}
