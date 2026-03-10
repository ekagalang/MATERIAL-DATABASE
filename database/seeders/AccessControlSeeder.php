<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $permissions = [
        'calculations.view',
        'calculations.manage',
        'dashboard.view',
        'logs.view',
        'materials.view',
        'materials.manage',
        'recommendations.manage',
        'roles.manage',
        'skills.view',
        'store-search-radius.manage',
        'stores.view',
        'stores.manage',
        'units.view',
        'units.manage',
        'users.manage',
        'work-items.view',
        'work-items.manage',
        'work-taxonomy.manage',
        'workers.view',
    ];

    public function run(): void
    {
        $guardName = 'web';

        foreach ($this->permissions as $permissionName) {
            Permission::findOrCreate($permissionName, $guardName);
        }

        $adminRole = Role::findOrCreate('Super Admin', $guardName);
        $adminRole->syncPermissions($this->permissions);

        $adminUser = User::updateOrCreate(
            ['email' => (string) env('ADMIN_EMAIL', 'admin@hope2.kanggo')],
            [
                'name' => (string) env('ADMIN_NAME', 'Administrator'),
                'password' => Hash::make((string) env('ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ],
        );

        if (!$adminUser->hasRole($adminRole)) {
            $adminUser->assignRole($adminRole);
        }
    }
}
