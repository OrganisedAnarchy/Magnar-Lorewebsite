<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SwadeshListTest extends TestCase
{
    public function test_list_has_207_unique_entries(): void
    {
        $list = (require __DIR__.'/../../config/swadesh.php')['list'];

        $this->assertCount(207, $list);
        $this->assertCount(207, array_unique($list));
    }
}
