<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailDeliveryTipTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_email_screen_includes_inbox_spam_tip_in_english(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('data-email-delivery-tip', false)
            ->assertSee('find our email', false)
            ->assertSee('Spam/Junk', false)
            ->assertSee('Verification emails may occasionally land there', false);
    }

    public function test_verify_email_screen_includes_arabic_tip_copy(): void
    {
        $user = User::factory()->unverified()->create(['locale' => 'ar']);

        $this->actingAs($user)
            ->withSession(['locale' => 'ar'])
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('مش لاقي الرسالة؟', false)
            ->assertSee('Spam / Junk', false);
    }

    public function test_reset_code_screen_uses_password_reset_tip_copy(): void
    {
        $this->withSession([
            'locale' => 'en',
            'password_reset_email' => 'member@example.com',
        ])
            ->get('/forgot-password/verify')
            ->assertOk()
            ->assertSee('data-email-delivery-tip', false)
            ->assertSee('Password reset emails may occasionally land there', false)
            ->assertDontSee('Verification emails may occasionally land there', false);
    }

    public function test_forgot_password_form_does_not_claim_delivery_before_request(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertDontSee('data-email-delivery-tip', false);
    }
}
