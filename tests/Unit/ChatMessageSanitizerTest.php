<?php

namespace Tests\Unit;

use App\Support\ChatMessageSanitizer;
use PHPUnit\Framework\TestCase;

class ChatMessageSanitizerTest extends TestCase
{
    public function test_strips_html_and_trims(): void
    {
        $clean = ChatMessageSanitizer::clean("  <script>alert('x')</script>Hello <b>team</b>  ");

        $this->assertSame("alert('x')Hello team", $clean);
    }

    public function test_collapses_excessive_newlines(): void
    {
        $clean = ChatMessageSanitizer::clean("Hi\n\n\n\nthere");

        $this->assertSame("Hi\n\nthere", $clean);
    }
}
