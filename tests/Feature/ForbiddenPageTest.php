<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ForbiddenPageTest extends TestCase
{
    public function test_forbidden_response_uses_safe_custom_page(): void
    {
        $this->withoutVite();
        $this->app['config']->set('app.debug', false);

        Route::get('/test-forbidden', function () {
            abort(403, 'Submission ini milik Budi');
        });

        $response = $this->get('/test-forbidden');

        $response->assertForbidden()
            ->assertSee('Akses ditolak')
            ->assertSee('hubungi administrator')
            ->assertDontSee('Submission ini milik Budi');
    }
}