<?php

use App\Enums\Permission as PermissionName;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Permissions live in a migration rather than a seeder so every environment,
     * including production and the test suite, has them without an extra step.
     * super-admin needs no grants: it passes every check (see AppServiceProvider).
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionName::values() as $name) {
            Permission::findOrCreate($name);
        }

        Role::findOrCreate('super-admin');
        Role::findOrCreate('admin')->syncPermissions(PermissionName::values());
        Role::findOrCreate('staff')->syncPermissions(PermissionName::viewOnly());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::query()->whereIn('name', PermissionName::values())->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
