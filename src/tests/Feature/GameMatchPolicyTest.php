<?php

namespace Tests\Feature;

use App\Models\GameMatch;
use App\Models\User;
use App\Policies\GameMatchPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\Support\CreatesBasketballDomain;
use Tests\TestCase;

class GameMatchPolicyTest extends TestCase
{
    use CreatesBasketballDomain;
    use RefreshDatabase;

    protected function seedViewMatchPermission(User $user): void
    {
        Permission::firstOrCreate(['name' => 'view_game::match', 'guard_name' => 'web']);
        $user->givePermissionTo('view_game::match');
    }

    public function test_consulta_cannot_scout(): void
    {
        ['match' => $match] = $this->createTeamWithMatch();
        $user = $this->createUserWithRole('consulta');
        $this->seedViewMatchPermission($user);

        $policy = new GameMatchPolicy;
        $this->assertFalse($policy->scout($user, $match));
    }

    public function test_treinador_can_scout_with_permission(): void
    {
        ['match' => $match] = $this->createTeamWithMatch();
        $user = $this->createUserWithRole('treinador');
        $this->seedViewMatchPermission($user);

        $policy = new GameMatchPolicy;
        $this->assertTrue($policy->scout($user, $match));
    }
}