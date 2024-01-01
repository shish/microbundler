<?php

declare(strict_types=1);

namespace MicroBundler;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Depends;

class MicroBundlerTest extends TestCase
{
    public function test_empty()
    {
        $m = new MicroBundler();
        [$css, $map] = $m->gen("mini.css");
        $this->assertEquals(
            "/*# sourceMappingURL=mini.css.map */",
            $css
        );
        $this->assertEquals(3, $map["version"]);
        $this->assertEquals([], $map["sources"]);
        $this->assertEquals("", $map["mappings"]);
    }

    public function test_css_basic()
    {
        $m = new MicroBundler();
        $m->addSource("foo.css", ".foo { color: red; }");
        $m->addSource("bar.css", ".bar { color: green; }\n.bar2 { color: blue; }");
        [$css, $map] = $m->gen("mini.css");
        $this->assertEquals(
            ".foo { color: red; }
.bar { color: green; }
.bar2 { color: blue; }
/*# sourceMappingURL=mini.css.map */",
            $css
        );
        $this->assertEquals(3, $map["version"]);
        $this->assertEquals(["foo.css", "bar.css"], $map["sources"]);
        $this->assertEquals([], $map["names"]);
        $this->assertEquals("AAAA;ACAA;AACA", $map["mappings"]);
    }

    public function test_css_relative_source()
    {
        $m = new MicroBundler();
        $m->addSource("source/foo.css", ".foo { color: red; }");
        [$css, $map] = $m->gen("gen/mini.css");
        $this->assertEquals(["../source/foo.css"], $map["sources"]);
    }

    public function test_css_process_file()
    {
        // no change
        $this->assertEquals(
            "foo\n\n\nbar",
            MicroBundler::process_file("foo\n\n\nbar", "in.css", "out.css")
        );

        // remove block comment - dangerous??
        /*
        $this->assertEquals(
            "foo\n\n\nbar",
            MicroBundler::process_file("foo\n\/* this is a comment\n* over multiple lines *\/\nbar", "in.css", "out.css")
        );
        */

        // change URLs from "relative to source file" to "relative to output file"
        // same dir
        $this->assertEquals(
            "url(foo.png)",
            MicroBundler::process_file("url(foo.png)", "in.css", "out.css")
        );
        // sibling dir
        $this->assertEquals(
            "url(../source/foo.png)",
            MicroBundler::process_file("url(foo.png)", "source/in.css", "gen/out.css")
        );
    }

    #[Depends("test_css_process_file")]
    public function test_css_relative_url()
    {
        $m = new MicroBundler();
        $m->addSource("source/foo.css", ".foo { background-image: url(foo.png); }");
        [$css, $map] = $m->gen("gen/mini.css");
        $this->assertEquals(
            ".foo { background-image: url(../source/foo.png); }
/*# sourceMappingURL=mini.css.map */",
            $css
        );
    }
}
