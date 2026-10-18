<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountSuspensionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync')->assertSuccessful();
    }

    public function test_admin_can_suspend_a_user_with_a_reason(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->patch(route('users.suspend', $user), ['reason' => 'Contract ended'])
            ->assertRedirect()
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->suspended_at);
        $this->assertTrue($user->suspendedBy->is($admin));
        $this->assertSame('Contract ended', $user->suspension_reason);
    }

    public function test_suspension_deletes_stored_sessions_and_remember_token(): void
    {
        config(['session.driver' => 'database']);

        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->create(['remember_token' => 'old-token']);

        DB::table('sessions')->insert([
            ['id' => 'session-a', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'session-b', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'session-c', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()],
        ]);

        $this->actingAs($admin)->patch(route('users.suspend', $user));

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertTrue(DB::table('sessions')->where('id', 'session-c')->exists(), 'Other users keep their sessions.');
        $this->assertNull($user->fresh()->remember_token);
    }

    public function test_suspended_user_is_signed_out_on_the_next_request(): void
    {
        $user = User::factory()->create()->assignRole('admin');

        $this->actingAs($user)->get(route('roles.index'))->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->get(route('roles.index'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspended_user_gets_json_403_on_api_style_requests(): void
    {
        $user = User::factory()->suspended()->create()->assignRole('admin');

        $this->actingAs($user)
            ->getJson(route('roles.index'))
            ->assertForbidden()
            ->assertJson(['message' => 'Your account has been suspended.']);
    }

    public function test_suspended_user_cannot_sign_in(): void
    {
        $user = User::factory()->suspended()->create(['email' => 'ali@example.com']);

        $this->post('/login', ['email' => 'ali@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Your account has been suspended. Please contact your administrator.']);

        $this->assertGuest();
    }

    public function test_active_user_can_sign_in(): void
    {
        $user = User::factory()->create(['email' => 'sara@example.com']);
        $user->assignRole('admin');

        $this->post('/login', ['email' => 'sara@example.com', 'password' => 'password'])
            ->assertRedirect(route('roles.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_shows_a_generic_error(): void
    {
        User::factory()->create(['email' => 'omar@example.com']);

        $this->post('/login', ['email' => 'omar@example.com', 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
    }

    public function test_admin_cannot_suspend_themselves(): void
    {
        $admin = User::factory()->create()->assignRole('admin');

        $this->actingAs($admin)
            ->patch(route('users.suspend', $admin))
            ->assertSessionHasErrors('user');

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_suspended_user_can_be_reactivated(): void
    {
        $admin = User::factory()->create()->assignRole('admin');
        $user = User::factory()->suspended()->create();

        $this->actingAs($admin)->patch(route('users.activate', $user))->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertNull($user->suspended_at);
    }

    public function test_users_without_manage_permission_cannot_suspend(): void
    {
        $viewer = User::factory()->create()->givePermissionTo('users.view');
        $target = User::factory()->create();

        $this->actingAs($viewer)->get(route('users.index'))->assertOk();
        $this->actingAs($viewer)->patch(route('users.suspend', $target))->assertForbidden();

        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_user_list_can_be_filtered_by_status(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Person'])->assignRole('admin');
        User::factory()->create(['name' => 'Active Person']);
        User::factory()->suspended()->create(['name' => 'Suspended Person']);

        $this->actingAs($admin)
            ->get(route('users.index', ['status' => 'suspended']))
            ->assertOk()
            ->assertSee('Suspended Person')
            ->assertDontSee('Active Person');
    }
}
