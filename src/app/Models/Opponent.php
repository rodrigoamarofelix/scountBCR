<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opponent extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'short_name', 'logo_path'];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }

    /**
     * Logotipo do adversário ou de equipe cadastrada com o mesmo nome/sigla.
     */
    public function resolvedLogoPath(): ?string
    {
        if (filled($this->logo_path)) {
            return $this->logo_path;
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
