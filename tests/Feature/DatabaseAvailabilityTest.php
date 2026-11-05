<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DatabaseAvailabilityTest extends TestCase
{
    private function registerFailure(int $code): void
    {
        Route::get('/_test/database-unavailable', function () use ($code) {
            $error = new \PDOException('Private database diagnostic', $code);
            $error->errorInfo = ['HY000', $code, 'Private database diagnostic'];
            throw new QueryException('select private_table', [], $error);
        });
    }

    public function test_connection_outage_has_a_safe_retry_screen_without_sql_details(): void
    {
        $this->registerFailure(2002);
        $this->get('/_test/database-unavailable')->assertStatus(503)->assertHeader('Retry-After', '10')
            ->assertSee('Temporarily Unavailable')->assertSee('Try Again')->assertDontSee('private_table')->assertDontSee('Private database diagnostic');
    }

    public function test_ajax_outage_has_a_retryable_503_without_internal_details(): void
    {
        $this->registerFailure(2013);
        $this->getJson('/_test/database-unavailable')->assertStatus(503)
            ->assertJsonPath('message', 'This service is temporarily unavailable. Please try again shortly.')
            ->assertDontSee('private_table');
    }

    public function test_unrelated_query_errors_are_not_mislabeled_as_a_temporary_outage(): void
    {
        $this->registerFailure(1064);
        $this->getJson('/_test/database-unavailable')->assertStatus(500);
    }
}
