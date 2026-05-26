<?php
namespace App\Services;
use App\Models\User; use Illuminate\Support\Facades\Hash; use Illuminate\Validation\ValidationException;
class AuthService
{
    public function login(array $data): array {
        $user = User::with('roles')->where('email',$data['email'])->first();
        if(!$user || !Hash::check($data['password'],$user->password)) throw ValidationException::withMessages(['email'=>'Invalid credentials.']);
        if(!$user->is_active) throw ValidationException::withMessages(['email'=>'This account is inactive.']);
        return ['token'=>$user->createToken($data['device_name']??'api-token')->plainTextToken,'user'=>$user];
    }
    public function logout(User $user): void { $user->currentAccessToken()?->delete(); }
}
