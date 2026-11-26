<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Restaura login e permissões Shield sem apagar partidas nem cadastros.
 */
class EnsureScountAccess extends Command
{
    protected $signature = 'scount:ensure-access';

    protected $description = 'Regenera permissões Shield e o usuário admin (não apaga partidas).';

    public function handle(): int
    {
        $this->warn('Este comando NÃO roda migrate:fresh — seus dados de partidas permanecem.');

        Artisan::call('shield:generate', [
            '--all' => true,
            '--panel' => 'admin',
            '--option' => 'permissions',
            '--no-interaction' => true,
        ]);
        $this->info('Permissões geradas.');

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RoleAndAdminSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\ShieldRolePermissionsSeeder', '--force' => true]);
        Artisan::call('permission:cache-reset');

        $this->newLine();
        $this->info('Acesso restaurado: admin@scountbcr.local / password');

        return self::SUCCESS;
    }
}