<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function preferredLandingRoute(): string
    {
        $routeMap = [
            'dashboard.view' => 'dashboard',
            'materials.view' => 'materials.index',
            'materials.manage' => 'materials.index',
            'stores.view' => 'stores.index',
            'stores.manage' => 'stores.index',
            'work-items.view' => 'work-items.index',
            'work-items.manage' => 'work-items.index',
            'calculations.view' => 'material-calculations.index',
            'calculations.manage' => 'material-calculations.create',
            'projects.view' => 'work-items.index',
            'projects.manage' => 'work-items.index',
            'workers.view' => 'workers.index',
            'skills.view' => 'skills.index',
            'units.view' => 'units.index',
            'units.manage' => 'units.index',
            'recommendations.manage' => 'settings.recommendations.index',
            'store-search-radius.manage' => 'settings.store-search-radius.index',
            'work-taxonomy.manage' => 'settings.work-areas.index',
            'settings.manage' => 'settings.work-areas.index',
            'roles.manage' => 'settings.roles.index',
            'users.manage' => 'settings.users.index',
            'logs.view' => 'logs.index',
        ];

        foreach ($routeMap as $permission => $routeName) {
            if ($this->can($permission)) {
                return route($routeName, absolute: false);
            }
        }

        return route('access.pending', absolute: false);
    }
}
