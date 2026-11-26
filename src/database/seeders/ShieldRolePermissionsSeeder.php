<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ShieldRolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $all = Permission::query()->pluck('name')->all();

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $treinador = Role::firstOrCreate(['name' => 'treinador', 'guard_name' => 'web']);
        $analista = Role::firstOrCreate(['name' => 'analista', 'guard_name' => 'web']);
        $consulta = Role::firstOrCreate(['name' => 'consulta', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        if ($all !== []) {
            $admin->syncPermissions($all);
        }

        $treinadorNames = collect($all)
            ->reject(fn (string $name) => str_starts_with($name, 'force_delete'))
            ->reject(fn (string $name) => str_contains($name, 'role'))
            ->reject(fn (string $name) => str_contains($name, '_user'))
            ->values()
            ->all();

        if ($treinadorNames !== []) {
            $treinador->syncPermissions($treinadorNames);
        }

        $analistaNames = collect($all)
            ->filter(fn (string $name) => str_starts_with($name, 'view')
                || str_starts_with($name, 'page_')
                || in_array($name, ['update_game::match', 'update_athlete'], true))
            ->values()
            ->all();

        if ($analistaNames !== []) {
            $analista->syncPermissions($analistaNames);
        }

        $consultaNames = collect($all)
            ->filter(fn (string $name) => str_starts_with($name, 'view')
                || in_array($name, ['page_StatsDashboard', 'page_AthleteComparison'], true))
            ->values()
            ->all();

        if ($consultaNames !== []) {
            $consulta->syncPermissions($consultaNames);
        }

        $this->ensureAdministratorUser();
    }

    protected function ensureAdministratorUser(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@scountbcr.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles(['super_admin', 'administrador', 'panel_user']);
    }
}