<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'leads.view',
            'leads.create',
            'leads.update',
            'leads.delete',
            'scraper.view',
            'scraper.run',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('super_admin');
        $agent = Role::findOrCreate('agent');

        $agent->syncPermissions($permissions);
    }
}
