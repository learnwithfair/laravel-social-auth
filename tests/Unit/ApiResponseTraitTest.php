<?php
namespace RahatulRabbi\SocialAuth\Tests\Unit;

use RahatulRabbi\SocialAuth\Tests\TestCase;
use RahatulRabbi\SocialAuth\Traits\ApiResponse;

class ApiResponseTraitTest extends TestCase
{
    /**
     * Concrete class that uses the trait so we can test it in isolation.
     */
    protected object $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new class {
            use ApiResponse;

            public function callSuccess(mixed $data, string $message, int $code)
            {
                return $this->success($data, $message, $code);
            }

            public function callError(mixed $data, string $message, int $code)
            {
                return $this->error($data, $message, $code);
            }
        };
    }

    /** @test */
    public function success_returns_a_200_json_response_with_correct_structure(): void
    {
        $response = $this->subject->callSuccess(['key' => 'value'], 'All good', 200);
        $body     = json_decode($response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($body['success']);
        $this->assertSame('All good', $body['message']);
        $this->assertSame(['key' => 'value'], $body['data']);
    }

    /** @test */
    public function error_returns_the_given_status_code_and_correct_structure(): void
    {
        $response = $this->subject->callError(null, 'Something went wrong', 422);
        $body     = json_decode($response->getContent(), true);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertFalse($body['success']);
        $this->assertSame('Something went wrong', $body['message']);
        $this->assertNull($body['data']);
    }

    /** @test */
    public function success_defaults_to_status_200(): void
    {
        $response = $this->subject->callSuccess(null, 'ok', 200);

        $this->assertSame(200, $response->getStatusCode());
    }
}
