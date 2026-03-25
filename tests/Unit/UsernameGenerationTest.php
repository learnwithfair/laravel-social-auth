<?php
namespace RahatulRabbi\SocialAuth\Tests\Unit;

use App\Models\User;
use RahatulRabbi\SocialAuth\Http\Controllers\SocialAuthController;
use RahatulRabbi\SocialAuth\Tests\TestCase;

class UsernameGenerationTest extends TestCase
{
    protected SocialAuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SocialAuthController();
    }

    /** @test */
    public function it_slugifies_a_plain_name(): void
    {
        $username = $this->callGenerateUsername('John Doe');

        $this->assertSame('john_doe', $username);
    }

    /** @test */
    public function it_handles_single_word_names(): void
    {
        $username = $this->callGenerateUsername('Alice');

        $this->assertSame('alice', $username);
    }

    /** @test */
    public function it_falls_back_to_user_when_name_slugifies_to_empty(): void
    {
        $username = $this->callGenerateUsername('---');

        $this->assertSame('user', $username);
    }

    /** @test */
    public function it_appends_numeric_suffix_to_resolve_conflicts(): void
    {
        // Seed a user that already owns 'john_doe'
        User::factory()->create(['username' => 'john_doe']);

        $username = $this->callGenerateUsername('John Doe');

        $this->assertSame('john_doe_1', $username);
    }

    /** @test */
    public function it_keeps_incrementing_until_unique(): void
    {
        User::factory()->create(['username' => 'john_doe']);
        User::factory()->create(['username' => 'john_doe_1']);
        User::factory()->create(['username' => 'john_doe_2']);

        $username = $this->callGenerateUsername('John Doe');

        $this->assertSame('john_doe_3', $username);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function callGenerateUsername(string $name): string
    {
        $reflection = new \ReflectionMethod(SocialAuthController::class, 'generateUniqueUsername');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, $name, User::class, 'username');
    }
}
