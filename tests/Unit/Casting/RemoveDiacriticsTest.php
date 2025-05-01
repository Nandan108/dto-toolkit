<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class RemoveDiacriticsTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testRemoveDiacriticsComponents(): void
    {
        $dto = new FullDto();

        // intl available -- transliteration happens via intl
        $this->casterTest(new CastTo\RemoveDiacritics(), 'Café', 'Cafe', [true]);

        // intl not available -- transliteration happens via fallback
        $this->casterTest(new CastTo\RemoveDiacritics(), 'Café', 'Cafe', [false]);

        $rd = new CastTo\RemoveDiacritics();
        $this->assertSame('Cafe', $rd->cast('Café', [false], $dto));
    }
}
