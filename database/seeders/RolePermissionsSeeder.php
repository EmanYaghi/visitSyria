<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionsSeeder extends Seeder
{

    public function run(): void
    {
        $superAdminRole=Role::findOrCreate('super_admin','api');
        $adminRole=Role::findOrCreate('admin','api');
        $clientRole=Role::findOrCreate('client','api');

        $permissions=[
            'delete_trip','update_trip','create_trip','index_trip'
        ];
        foreach($permissions as $permissionName){
            Permission::findOrCreate($permissionName,'api');
        }
        $superAdminRole->syncPermissions($permissions);//delete old permission and keep those inside the $permission
        $superAdminRole->syncPermissions($permissions);
        $clientRole->givePermissionTo(['create_trip','index_trip']);//add permission on top of old ones
        $adminUser=User::factory()->create([
            'name'=>'admin',
            'email'=>'admin@example.com',
            'password'=>bcrypt('password'),
        ]);
        $adminUser->assignRole($adminRole);
        $permissions=$adminRole->permissions()->pluck('name')->toArray();
        $adminUser->givePermissionTo($permissions);

         $clientUser=User::factory()->create([
            'name'=>'client',
            'email'=>'client@example.com',
            'password'=>bcrypt('password'),
        ]);
        $clientUser->assignRole($clientRole);
        $permissions=$clientRole->permissions()->pluck('name')->toArray();
        $clientUser->givePermissionTo($permissions);
    }
}
