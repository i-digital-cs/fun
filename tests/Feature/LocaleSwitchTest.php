<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_sets_arabic_and_rtl_direction(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('design.welcome_guest', [], 'ar'), false);
    }
}
