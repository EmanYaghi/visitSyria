<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::findOrCreate('super_admin', 'api');
        $adminRole = Role::findOrCreate('admin', 'api');
        $clientRole = Role::findOrCreate('client', 'api');

        $permissions = [
            'createEvent', 'updateEvent', 'deleteEvent', 'indexTrip', 'createTrip', 'updateTrip', 'deleteTrip'
        ];

        foreach ($permissions as $permissionName) {
            Permission::findOrCreate($permissionName, 'api');
        }

        $superAdminRole->syncPermissions($permissions);
        $adminRole->syncPermissions(['indexTrip', 'createTrip', 'updateTrip', 'deleteTrip']);
        $clientRole->syncPermissions(['indexTrip', 'createTrip']);

        $adminUser = User::factory()
            ->hasProfile()
            ->create([
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'is_verified' => 1,
            ]);
        $adminUser->assignRole($adminRole);

        $clientUser = User::factory()
            ->hasProfile()
            ->create([
                'email' => 'client@example.com',
                'password' => Hash::make('password'),
                'is_verified' => 1,
            ]);
        $clientUser->assignRole($clientRole);

        $superAdminUser = User::factory()
            ->hasProfile()
            ->create([
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'is_verified' => 1,
            ]);
        $superAdminUser->assignRole($superAdminRole);
    }
}
