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
            'services.view',
            'services.create',
            'services.update',
            'services.delete',
            'ai.providers.view',
            'ai.providers.create',
            'ai.providers.update',
            'ai.providers.delete',
            'ai.models.view',
            'ai.models.create',
            'ai.models.update',
            'ai.models.delete',
            'ai.employees.view',
            'ai.employees.create',
            'ai.employees.update',
            'ai.employees.delete',
            'ai.prompts.view',
            'ai.prompts.create',
            'ai.prompts.update',
            'ai.prompts.delete',
            'ai.knowledge.view',
            'ai.knowledge.create',
            'ai.knowledge.update',
            'ai.knowledge.delete',
            'ai.logs.view',
            'voice.providers.view',
            'voice.providers.create',
            'voice.providers.update',
            'voice.providers.delete',
            'voice.calls.view',
            'voice.calls.create',
            'email.providers.view',
            'email.providers.create',
            'email.providers.update',
            'email.providers.delete',
            'email.campaigns.view',
            'email.campaigns.create',
            'email.campaigns.update',
            'email.campaigns.delete',
            'email.campaigns.send',
            'email.sends.view',
            'email.sends.create',
            'crm.deals.manage',
            'crm.tasks.view',
            'crm.tasks.manage',
            'crm.quotes.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        Role::findOrCreate('super_admin');
        $agent = Role::findOrCreate('agent');

        $agent->syncPermissions($permissions);
    }
}
