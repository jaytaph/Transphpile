<?php

namespace Transphpile\Tests\IO;

use Transphpile\IO\IOInterface;
use Transphpile\Tests\TestCase;
use Transphpile\IO\NullIO;


class NullIOTest extends TestCase
{
    /** @var IOInterface */
    protected $nullio;

    public function setUp() {
        $this->nullio = new NullIO();
    }

    public function testGetOption() {
        $this->assertFalse($this->nullio->getOption('foo'));
    }

    public function testGetOptions() {
        $this->assertEmpty($this->nullio->getOptions());
    }

    public function testGetArgument() {
        $this->assertFalse($this->nullio->getArgument('foo'));
    }

    public function testGetArguments() {
        $this->assertEmpty($this->nullio->getArguments());
    }

    public function testOutput() {
        $this->assertNull($this->nullio->output("foobar"));
    }

    public function testVerbose() {
        $this->assertNull($this->nullio->verbose("foobar", "tag"));
    }

    public function testVeryVerbose() {
        $this->assertNull($this->nullio->veryVerbose("foobar", "tag"));
    }

    public function testDebug() {
        $this->assertNull($this->nullio->debug("foobar", "tag"));
    }

}
