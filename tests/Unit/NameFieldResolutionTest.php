<?php

namespace RahatulRabbi\SocialAuth\Tests\Unit;

use RahatulRabbi\SocialAuth\Http\Controllers\SocialAuthController;
use RahatulRabbi\SocialAuth\Tests\TestCase;

class NameFieldResolutionTest extends TestCase
{
    protected SocialAuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new SocialAuthController();
    }

    /** @test */
    public function single_strategy_writes_full_name_to_configured_column(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'single');
        $this->app['config']->set('social-auth.name_field.column', 'name');

        $fields = $this->callResolveNameFields('Alice Walker');

        $this->assertSame(['name' => 'Alice Walker'], $fields);
    }

    /** @test */
    public function single_strategy_respects_custom_column_name(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'single');
        $this->app['config']->set('social-auth.name_field.column', 'full_name');

        $fields = $this->callResolveNameFields('Alice Walker');

        $this->assertSame(['full_name' => 'Alice Walker'], $fields);
    }

    /** @test */
    public function split_strategy_separates_first_and_last_name(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'split');
        $this->app['config']->set('social-auth.name_field.first', 'first_name');
        $this->app['config']->set('social-auth.name_field.last', 'last_name');

        $fields = $this->callResolveNameFields('Alice Walker');

        $this->assertSame([
            'first_name' => 'Alice',
            'last_name'  => 'Walker',
        ], $fields);
    }

    /** @test */
    public function split_strategy_respects_custom_column_names(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'split');
        $this->app['config']->set('social-auth.name_field.first', 'f_name');
        $this->app['config']->set('social-auth.name_field.last', 'l_name');

        $fields = $this->callResolveNameFields('Bob Smith');

        $this->assertSame([
            'f_name' => 'Bob',
            'l_name' => 'Smith',
        ], $fields);
    }

    /** @test */
    public function split_strategy_handles_single_word_name_gracefully(): void
    {
        $this->app['config']->set('social-auth.name_field.strategy', 'split');
        $this->app['config']->set('social-auth.name_field.first', 'first_name');
        $this->app['config']->set('social-auth.name_field.last', 'last_name');

        $fields = $this->callResolveNameFields('Madonna');

        $this->assertSame('Madonna', $fields['first_name']);
        $this->assertSame('',        $fields['last_name']);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function callResolveNameFields(string $name): array
    {
        $reflection = new \ReflectionMethod(SocialAuthController::class, 'resolveNameFields');
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, $name);
    }
}