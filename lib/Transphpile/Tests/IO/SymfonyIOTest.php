<?php

namespace Transphpile\Tests\IO;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\StreamOutput;
use Transphpile\IO\IOInterface;
use Transphpile\Tests\TestCase;
use Transphpile\IO\SymfonyIO;


class SymfonyIOTest extends TestCase
{
    /** @var IOInterface */
    protected $symfonyio;

    protected function createOutput($level)
    {
        $definition = new InputDefinition(array(
            new InputArgument('name', InputArgument::REQUIRED),
            new InputOption('bar', 'b', InputOption::VALUE_REQUIRED),
        ));
        $input = new ArrayInput(array('name' => 'foo', '--bar' => 'foobar'), $definition);

        $this->stream = fopen('php://memory', 'a', false);
        $output = new StreamOutput($this->stream, $level);

        return new SymfonyIO($input, $output);
    }

    public function setUp() {
        $this->symfonyio = $this->createOutput(Output::VERBOSITY_NORMAL);
    }

    public function testGetOption() {
        $this->assertEquals('foobar', $this->symfonyio->getOption('bar'));
    }

    public function testGetOptions() {
        $this->assertEquals(array('bar' => 'foobar'), $this->symfonyio->getOptions());
    }

    public function testGetArgument() {
        $this->assertEquals('foo', $this->symfonyio->getArgument('name'));
    }

    public function testGetArguments() {
        $this->assertEquals(array('name' => 'foo'), $this->symfonyio->getArguments());
    }

    public function testOutput() {
        // No output in normal verbosity
        $symfonyio = $this->createOutput(Output::VERBOSITY_NORMAL);
        $symfonyio->output("foobar1");
        $symfonyio->verbose("foobar2", "tag2");
        $symfonyio->veryVerbose("foobar3", "tag3");
        $symfonyio->debug("foobar4", "tag4");
        rewind($this->stream);
        $this->assertEquals("foobar1\n", stream_get_contents($this->stream));

        // output in normal verbosity
        $symfonyio = $this->createOutput(Output::VERBOSITY_VERBOSE);
        $symfonyio->output("foobar1");
        $symfonyio->verbose("foobar2", "tag2");
        $symfonyio->veryVerbose("foobar3", "tag3");
        $symfonyio->debug("foobar4", "tag4");
        rewind($this->stream);
        $this->assertEquals("foobar1\n[tag2] foobar2\n", stream_get_contents($this->stream));

        $symfonyio = $this->createOutput(Output::VERBOSITY_VERY_VERBOSE);
        $symfonyio->output("foobar1");
        $symfonyio->verbose("foobar2", "tag2");
        $symfonyio->veryVerbose("foobar3", "tag3");
        $symfonyio->debug("foobar4", "tag4");
        rewind($this->stream);
        $this->assertEquals("foobar1\n[tag2] foobar2\n[tag3] foobar3\n", stream_get_contents($this->stream));

        $symfonyio = $this->createOutput(Output::VERBOSITY_DEBUG);
        $symfonyio->output("foobar1");
        $symfonyio->verbose("foobar2", "tag2");
        $symfonyio->veryVerbose("foobar3", "tag3");
        $symfonyio->debug("foobar4", "tag4");
        rewind($this->stream);
        $this->assertEquals("foobar1\n[tag2] foobar2\n[tag3] foobar3\n[tag4] foobar4\n", stream_get_contents($this->stream));
    }

}
