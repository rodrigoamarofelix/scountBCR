<?php

namespace App\Policies;

use App\Models\GameMatch;
use App\Models\User;

class GameMatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_game::match');
    }

    public function view(User $user, GameMatch $match): bool
    {
        return $user->can('view_game::match');
    }

    public function create(User $user): bool
    {
        return $user->can('create_game::match');
    }

    public function update(User $user, GameMatch $match): bool
    {
        return $user->can('update_game::match');
    }

    public function delete(User $user, GameMatch $match): bool
    {
        return $user->can('delete_game::match');
    }

    public function restore(User $user, GameMatch $match): bool
    {
        return $user->can('restore_game::match');
    }

    public function forceDelete(User $user, GameMatch $match): bool
    {
        return $user->can('force_delete_game::match');
    }

    public function scout(User $user, GameMatch $match): bool
    {
        if (! $user->can('view_game::match')) {
            return false;
        }

        if ($user->hasRole('consulta')) {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'administrador', 'treinador', 'analista']);
    }
}