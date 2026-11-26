<?php

namespace App\Http\Controllers\Admin;

use App\Exports\StatsReportExport;
use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, string $format, ReportService $reports)
    {
        $user = $request->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'type' => ['required', Rule::in(['match', 'athlete', 'team', 'comparison', 'evolution'])],
            'match_id' => ['nullable', 'integer'],
            'athlete_id' => ['nullable', 'integer'],
            'team_id' => ['nullable', 'integer'],
            'athlete_ids' => ['nullable', 'array'],
            'athlete_ids.*' => ['integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $type = $validated['type'];
        $this->authorizeExport($user, $type);

        if ($type === 'match' && empty($validated['match_id'])) {
            abort(422, 'Selecione uma partida.');
        }
        if ($type === 'athlete' && empty($validated['athlete_id'])) {
            abort(422, 'Selecione um atleta.');
        }
        if ($type === 'team' && empty($validated['team_id'])) {
            abort(422, 'Selecione uma equipe.');
        }
        if ($type === 'comparison' && count($validated['athlete_ids'] ?? []) < 2) {
            abort(422, 'Selecione pelo menos 2 atletas.');
        }

        $scoped = $this->scopedParams($type, $validated);
        $data = $reports->build($type, array_merge($validated, $scoped));

        $slug = 'scountbcr-'.$type.'-'.now()->format('Ymd-His');

        if ($format === 'pdf') {
            $view = match ($type) {
                'match' => 'reports.match',
                'athlete' => 'reports.athlete',
                'team' => 'reports.team',
                'comparison' => 'reports.comparison',
                'evolution' => 'reports.evolution',
            };

            $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'portrait');

            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$slug.'.pdf"',
            ]);
        }

        if ($format === 'xlsx') {
            $excel = $data['excel'];

            return Excel::download(
                new StatsReportExport($excel['headings'], $excel['rows'], $data['title']),
                $slug.'.xlsx'
            );
        }

        abort(404);
    }

    protected function authorizeExport($user, string $type): void
    {
        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($type === 'athlete') {
            abort_unless($user->can('page_AthleteProfile') || $user->can('page_ReportsHub'), 403);

            return;
        }

        abort_unless($user->can('page_ReportsHub'), 403);
    }

    protected function scopedParams(string $type, array $validated): array
    {
        return match ($type) {
            'match' => ['match_id' => (int) $validated['match_id']],
            'athlete' => array_filter([
                'athlete_id' => (int) $validated['athlete_id'],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            'team' => array_filter([
                'team_id' => (int) $validated['team_id'],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            'comparison' => array_filter([
                'athlete_ids' => $validated['athlete_ids'] ?? [],
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ], fn ($v) => $v !== null && $v !== '' && $v !== []),
            'evolution' => array_filter([
                'team_id' => $validated['team_id'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''),
            default => [],
        };
    }
}