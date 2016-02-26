<?php

namespace Transphpile\Tests\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Transphpile\Console\Application;
use Transphpile\Console\Command\Command;
use Transphpile\IO\NullIO;
use Transphpile\Tests\TestCase;

class MockIo extends NullIO { }

class MockApp extends Application
{
    function __construct()
    {
        parent::__construct();
        $this->io = new MockIo();
    }
}

class CommandTest extends TestCase
{

    public function testGetIOWithoutApp()
    {
        $command = new Command("foobar");
        $this->assertInstanceOf("Transphpile\\IO\\NullIO", $command->getIo());
    }

    public function testGetIOApp()
    {
        $command = new Command("foobar");

        $app = new MockApp();
        $app->add($command);

        $this->assertInstanceOf("Transphpile\\Tests\\Command\\MockIO", $command->getIo());

        $command->setIo(new NullIO());
        $this->assertInstanceOf("Transphpile\\IO\\NullIO", $command->getIo());
    }

    public function testGetDefaultSymfonyIO() {

        $app = new MockApp();
        $app->doRun(new ArrayInput(array()), new NullOutput());

        $this->assertInstanceOf("Transphpile\\IO\\SymfonyIO", $app->getIo());
    }


}
