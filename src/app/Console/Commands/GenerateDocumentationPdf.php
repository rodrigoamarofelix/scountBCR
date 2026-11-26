<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateDocumentationPdf extends Command
{
    protected $signature = 'scount:docs-pdf';

    protected $description = 'Gera o manual ScountBCR em PDF na pasta docs/ na raiz do projeto';

    public function handle(): int
    {
        $outputDir = file_exists('/docs') ? '/docs' : base_path('../docs');
        if (! is_dir($outputDir) && ! @mkdir($outputDir, 0775, true) && ! is_dir($outputDir)) {
            $outputDir = storage_path('app/docs');
            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0775, true);
            }
        }

        $outputFile = rtrim($outputDir, '/').'/ScountBCR-Manual.pdf';

        $pdf = Pdf::loadView('docs.manual', [
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        file_put_contents($outputFile, $pdf->output());

        $this->info("Manual gerado: {$outputFile}");

        return self::SUCCESS;
    }
}