<?php

namespace Learnwithfair\SocialAuth\Tests\Feature;

use Learnwithfair\SocialAuth\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    /** @test */
    public function it_runs_the_install_command_without_errors(): void
    {
        $this->artisan('social-auth:install', ['--skip-migration' => true])
             ->assertSuccessful();
    }
}