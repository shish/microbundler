<?php

declare(strict_types=1);

namespace MicroBundler;

use PHPUnit\Framework\TestCase;

class JSTest extends TestCase
{
    public function test_empty(): void
    {
        $m = new MicroBundler();
        [$css, $map] = $m->gen("mini.js");
        $this->assertEquals(
            "//# sourceMappingURL=mini.js.map",
            $css
        );
        $this->assertEquals(3, $map["version"]);
        $this->assertEquals([], $map["sources"]);
        $this->assertEquals("", $map["mappings"]);
    }

    public function test_basic(): void
    {
        $m = new MicroBundler();
        $m->addSource("foo.js", "function foo() { console.log('foo'); }");
        $m->addSource("bar.js", "function bar() { console.log('bar'); }\nfunction bar2() { console.log('bar2'); }");
        [$css, $map] = $m->gen("mini.js");
        $this->assertEquals(
            "function foo() { console.log('foo'); }
function bar() { console.log('bar'); }
function bar2() { console.log('bar2'); }
//# sourceMappingURL=mini.js.map",
            $css
        );
        $this->assertEquals(3, $map["version"]);
        $this->assertEquals(["foo.js", "bar.js"], $map["sources"]);
        $this->assertEquals([], $map["names"]);
        $this->assertEquals("AAAA;ACAA;AACA", $map["mappings"]);
    }

}
