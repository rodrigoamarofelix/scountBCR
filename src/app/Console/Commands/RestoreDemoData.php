<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Só adiciona dados demo se o banco estiver vazio (não destrói nada existente).
 */
class RestoreDemoData extends Command
{
    protected $signature = 'scount:seed-demo {--force : Roda DemoDataSeeder mesmo se já houver partidas}';

    protected $description = 'Cria equipes/partida demo (somente se não houver partidas, salvo --force).';

    public function handle(): int
    {
        if (GameMatch::query()->exists() && ! $this->option('force')) {
            $this->info('Já existem partidas no banco — nada foi alterado. Use --force apenas se souber o que faz.');

            return self::SUCCESS;
        }

        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\DemoDataSeeder', '--force' => true]);
        $this->info('DemoDataSeeder executado.');

        return self::SUCCESS;
    }
}