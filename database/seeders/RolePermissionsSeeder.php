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

        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_verified' => 1,
        ]);
        $adminUser->adminProfile()->create([
            'name_of_company'=>'company1',
            'name_of_owner'=>'owner1',
            'founding_date'=>'2025-07-22',
            'license_number'=>'754544',
            'phone'=>'0987654321',
            'country_code'=>'+963',
            'description'=>'jhfdhg kdjgh yeyghgj dkhgb kjdh',
            'location'=>'دمشق-باب توما',
            'latitude'=>'34.44',
            'longitude'=>'45.7',
            'status'=>'فعالة'
        ]);
        $adminUser->assignRole($adminRole);
        $adminUser2 = User::factory()->create([
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
            'is_verified' => 1,
        ]);
        $adminUser2->adminProfile()->create([
            'name_of_company'=>'company2',
            'name_of_owner'=>'owner2',
            'founding_date'=>'2025-07-22',
            'license_number'=>'754544',
            'phone'=>'0987654321',
            'country_code'=>'+963',
            'description'=>'jhfdhg kdjgh yeyghgj dkhgb kjdh',
            'location'=>'دمشق-باب توما',
            'latitude'=>'34.44',
            'longitude'=>'45.7',
            'status'=>'فعالة'
        ]);
        $adminUser2->assignRole($adminRole);
        $clientUser = User::factory()->create([
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'is_verified' => 1,
        ]);
        $clientUser->profile()->create([
            'first_name'=>'maya',
            'last_name'=>'maya',
            'date_of_birth'=>'2000-07-22',
            'gender'=>'female',
            'country'=>'syria',
            'phone'=>'0987654321',
            'country_code'=>'+963',
        ]);
        $clientUser->assignRole($clientRole);
        $clientUser2 = User::factory()->create([
            'email' => 'client2@example.com',
            'password' => Hash::make('password'),
            'is_verified' => 1,
        ]);
        $clientUser2->profile()->create([
            'first_name'=>'maher',
            'last_name'=>'maher',
            'date_of_birth'=>'1999-07-22',
            'gender'=>'male',
            'country'=>'lebanon',
            'phone'=>'0987654321',
            'country_code'=>'+965',
        ]);
        $clientUser2->assignRole($clientRole);
        $superAdminUser = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password'),
            'is_verified' => 1,
        ]);
        $superAdminUser->assignRole($superAdminRole);
    }
}
