<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opponent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'logo_path', 'linked_team_id'];

    protected static function booted(): void
    {
        static::saving(function (Opponent $opponent) {
            if ($opponent->linked_team_id && blank($opponent->logo_path)) {
                $logo = Team::query()->whereKey($opponent->linked_team_id)->value('logo_path');
                if ($logo) {
                    $opponent->logo_path = $logo;
                }
            }
        });
    }

    public function linkedTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'linked_team_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Logotipo do adversário, da equipe espelho ou de equipe com o mesmo nome/sigla.
     */
    public function resolvedLogoPath(): ?string
    {
        if (filled($this->logo_path)) {
            return $this->logo_path;
        }

        if ($this->linked_team_id) {
            $path = $this->linkedTeam?->logo_path ?? Team::query()->whereKey($this->linked_team_id)->value('logo_path');
            if ($path) {
                return $path;
            }
        }

        if (filled($this->short_name)) {
            $fromShort = Team::query()
                ->whereNotNull('logo_path')
                ->where('short_name', $this->short_name)
                ->value('logo_path');

            if ($fromShort) {
                return $fromShort;
            }
        }

        if (filled($this->name)) {
            return Team::query()
                ->whereNotNull('logo_path')
                ->where('name', $this->name)
                ->value('logo_path');
        }

        return null;
    }
}