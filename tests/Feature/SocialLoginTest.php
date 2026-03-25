<?php
namespace RahatulRabbi\SocialAuth\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RahatulRabbi\SocialAuth\Tests\TestCase;

class SocialLoginTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Build a fake Socialite user and mock the Socialite driver so that
     * userFromToken() returns it without making any real HTTP requests.
     */
    protected function mockSocialiteUser(array $overrides = []): SocialiteUser
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);

        $socialiteUser->allows('getEmail')->andReturn($overrides['email'] ?? 'john@example.com');
        $socialiteUser->allows('getName')->andReturn($overrides['name'] ?? 'John Doe');
        $socialiteUser->allows('getAvatar')->andReturn($overrides['avatar'] ?? null);
        $socialiteUser->allows('getId')->andReturn($overrides['id'] ?? 'google-uid-123');

        $provider = Mockery::mock();
        $provider->allows('stateless')->andReturnSelf();
        $provider->allows('userFromToken')->andReturn($socialiteUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        return $socialiteUser;
    }

    /**
     * Standard valid POST payload.
     */
    protected function validPayload(array $overrides = []): array
    {
        return array_merge([
            'provider'    => 'google',
            'provider_id' => 'valid-token-123',
            'device'      => 'android',
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // Validation
    // -----------------------------------------------------------------------

    /** @test */
    public function it_rejects_a_request_missing_all_fields(): void
    {
        $response = $this->postJson('/api/social-login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider', 'provider_id', 'device']);
    }

    /** @test */
    public function it_rejects_an_unsupported_provider(): void
    {
        $response = $this->postJson('/api/social-login', $this->validPayload([
            'provider' => 'facebook',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    /** @test */
    public function it_rejects_an_unsupported_device(): void
    {
        $response = $this->postJson('/api/social-login', $this->validPayload([
            'device' => 'web',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['device']);
    }

    // -----------------------------------------------------------------------
    // Successful login and registration
    // -----------------------------------------------------------------------

    /** @test */
    public function it_registers_a_new_user_on_first_login(): void
    {
        $this->mockSocialiteUser(['email' => 'newuser@example.com', 'name' => 'New User']);

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'User registered successfully.',
            ])
            ->assertJsonStructure([
                'data' => ['user', 'token', 'token_type'],
            ]);

        $this->assertDatabaseHas('users', [
            'email'    => 'newuser@example.com',
            'name'     => 'New User',
            'provider' => 'google',
        ]);
    }

    /** @test */
    public function it_logs_in_an_existing_user(): void
    {
        // First login — creates the user
        $this->mockSocialiteUser(['email' => 'existing@example.com']);
        $this->postJson('/api/social-login', $this->validPayload());

        // Second login — should return "Login successful"
        $this->mockSocialiteUser(['email' => 'existing@example.com']);
        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
            ]);

        // Should not create a duplicate
        $this->assertDatabaseCount('users', 1);
    }

    /** @test */
    public function it_issues_a_sanctum_bearer_token(): void
    {
        $this->mockSocialiteUser();

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token']]);

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);
    }

    /** @test */
    public function it_works_with_an_ios_device(): void
    {
        $this->mockSocialiteUser(['email' => 'ios@example.com']);

        $response = $this->postJson('/api/social-login', $this->validPayload([
            'device' => 'ios',
        ]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_works_with_apple_provider(): void
    {
        $this->mockSocialiteUser(['email' => 'apple@example.com']);

        $response = $this->postJson('/api/social-login', $this->validPayload([
            'provider' => 'apple',
            'device'   => 'ios',
        ]));

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'email'    => 'apple@example.com',
            'provider' => 'apple',
        ]);
    }

    // -----------------------------------------------------------------------
    // Username generation
    // -----------------------------------------------------------------------

    /** @test */
    public function it_generates_a_unique_username_for_a_new_user(): void
    {
        $this->mockSocialiteUser(['email' => 'user@example.com', 'name' => 'Jane Smith']);

        $this->postJson('/api/social-login', $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email'    => 'user@example.com',
            'username' => 'jane_smith',
        ]);
    }

    /** @test */
    public function it_appends_a_numeric_suffix_when_username_already_exists(): void
    {
        // First user takes 'jane_smith'
        $this->mockSocialiteUser(['email' => 'jane1@example.com', 'name' => 'Jane Smith']);
        $this->postJson('/api/social-login', $this->validPayload());

        // Second user with same name should get 'jane_smith_1'
        $this->mockSocialiteUser(['email' => 'jane2@example.com', 'name' => 'Jane Smith']);
        $this->postJson('/api/social-login', $this->validPayload());

        $this->assertDatabaseHas('users', ['email' => 'jane1@example.com', 'username' => 'jane_smith']);
        $this->assertDatabaseHas('users', ['email' => 'jane2@example.com', 'username' => 'jane_smith_1']);
    }

    /** @test */
    public function it_skips_username_when_disabled_in_config(): void
    {
        $this->app['config']->set('social-auth.username.enabled', false);

        $this->mockSocialiteUser(['email' => 'nousername@example.com', 'name' => 'No Username']);

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200);

        // Username column should remain null
        $this->assertDatabaseHas('users', [
            'email'    => 'nousername@example.com',
            'username' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Name field strategy
    // -----------------------------------------------------------------------

    /** @test */
    public function it_writes_full_name_to_a_single_column(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'single');
        $this->app['config']->set('social-auth.name_field.column', 'name');

        $this->mockSocialiteUser(['email' => 'single@example.com', 'name' => 'Alice Walker']);

        $this->postJson('/api/social-login', $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email' => 'single@example.com',
            'name'  => 'Alice Walker',
        ]);
    }

    /** @test */
    public function it_splits_full_name_into_first_and_last_columns(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'split');
        $this->app['config']->set('social-auth.name_field.first', 'first_name');
        $this->app['config']->set('social-auth.name_field.last', 'last_name');

        $this->mockSocialiteUser(['email' => 'split@example.com', 'name' => 'Alice Walker']);

        $this->postJson('/api/social-login', $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email'      => 'split@example.com',
            'first_name' => 'Alice',
            'last_name'  => 'Walker',
        ]);
    }

    // -----------------------------------------------------------------------
    // Avatar
    // -----------------------------------------------------------------------

    /** @test */
    public function it_skips_avatar_download_when_disabled_in_config(): void
    {
        $this->app['config']->set('social-auth.avatar.enabled', false);

        $this->mockSocialiteUser([
            'email'  => 'noavatar@example.com',
            'avatar' => 'https://example.com/photo.jpg',
        ]);

        Http::fake(); // No HTTP calls should be made

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_skips_avatar_download_when_provider_returns_no_avatar(): void
    {
        $this->mockSocialiteUser([
            'email'  => 'nourl@example.com',
            'avatar' => null,
        ]);

        Http::fake();

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200);
        Http::assertNothingSent();
    }

    // -----------------------------------------------------------------------
    // Active status
    // -----------------------------------------------------------------------

    /** @test */
    public function it_sets_the_active_status_column_on_new_users(): void
    {
        $this->mockSocialiteUser(['email' => 'active@example.com']);

        $this->postJson('/api/social-login', $this->validPayload());

        $this->assertDatabaseHas('users', [
            'email'     => 'active@example.com',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_skips_active_status_when_disabled_in_config(): void
    {
        $this->app['config']->set('social-auth.active_status.enabled', false);

        $this->mockSocialiteUser(['email' => 'nostatus@example.com']);

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(200);
    }

    // -----------------------------------------------------------------------
    // Error handling
    // -----------------------------------------------------------------------

    /** @test */
    public function it_returns_422_when_socialite_returns_no_email(): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->allows('getEmail')->andReturn(null);
        $socialiteUser->allows('getName')->andReturn('No Email User');
        $socialiteUser->allows('getAvatar')->andReturn(null);
        $socialiteUser->allows('getId')->andReturn('uid-no-email');

        $provider = Mockery::mock();
        $provider->allows('stateless')->andReturnSelf();
        $provider->allows('userFromToken')->andReturn($socialiteUser);

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid social token or missing email.',
            ]);
    }

    /** @test */
    public function it_returns_500_when_socialite_throws_an_exception(): void
    {
        $provider = Mockery::mock();
        $provider->allows('stateless')->andReturnSelf();
        $provider->allows('userFromToken')->andThrow(new \Exception('Token expired'));

        Socialite::shouldReceive('buildProvider')->andReturn($provider);

        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Social login failed.',
            ]);
    }

    /** @test */
    public function it_returns_500_when_client_id_is_missing_from_config(): void
    {
        $this->app['config']->set('social-auth.providers.google.android.client_id', null);

        // No Socialite mock — the driver resolution itself should throw
        $response = $this->postJson('/api/social-login', $this->validPayload());

        $response->assertStatus(500)
            ->assertJson(['success' => false]);
    }
}
