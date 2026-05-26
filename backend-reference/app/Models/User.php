<?php
namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $fillable = ['branch_id','name','email','password','is_active'];
    protected $hidden = ['password','remember_token'];
    protected $casts = ['email_verified_at'=>'datetime','password'=>'hashed','is_active'=>'boolean'];
    public function branch(){return $this->belongsTo(Branch::class);}
    public function roles(){return $this->belongsToMany(Role::class);}
    public function hasRole(string $code): bool{return $this->roles()->where('code',$code)->exists();}
    public function hasAnyRole(array $codes): bool{return $this->roles()->whereIn('code',$codes)->exists();}
}
