<?php

namespace Tests\Feature;

use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    public function test_locale_switch_sets_arabic_and_rtl_direction(): void
    {
        $this->from(route('home'))
            ->post(route('locale.update'), ['locale' => 'ar'])
            ->assertRedirect();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee(__('home.headline', [], 'ar'), false);
    }
}
