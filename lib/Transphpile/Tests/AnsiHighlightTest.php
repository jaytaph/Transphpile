<?php

namespace Transphpile\Tests;


use Transphpile\AnsiHighlight;

class AnsiHighlightTest extends TestCase
{

    public function testHighlight()
    {
        $highlighter = new AnsiHighlight();
        $output = $highlighter->highlight("<?php print 'hello world!';");
        $this->assertEquals("1b5b33373b316d0a1b5b33343b316d3c3f706870c2a01b5b306d1b5b33333b316d7072696e74c2a01b5b306d1b5b33323b316d2768656c6c6fc2a0776f726c6421271b5b306d1b5b33333b316d3b1b5b306d0a1b5b306d0a0a", bin2hex($output));
    }

}
