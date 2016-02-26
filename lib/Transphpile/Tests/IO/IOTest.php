<?php

namespace Transphpile\Tests\IO;

use Transphpile\Tests\TestCase;
use Transphpile\IO\IO;
use Transphpile\IO\NullIO;

class MockIoTest {
    use IO;
}

class IOTest extends TestCase
{

    public function testIoTrait() {
        $tmp = new NullIO();

        $io = new MockIoTest();

        $this->assertNull($io->getIO());

        $io->setIO($tmp);
        $this->assertEquals($tmp, $io->getIO());
    }

}
