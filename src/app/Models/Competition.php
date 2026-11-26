<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Competition extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'season', 'description'];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class);
    }
}
