<?php

namespace PHPile\IO;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SymfonyIO implements IOInterface
{
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    public function getOption($option)
    {
        return $this->input->getOption($option);
    }

    public function getOptions()
    {
        return $this->input->getOptions();
    }

    public function getArgument($argument)
    {
        return $this->input->getArgument($argument);
    }

    public function getArguments()
    {
        return $this->input->getArguments();
    }

    public function output($message)
    {
        return $this->output->writeln($message);
    }

    public function verbose($message, $tag)
    {
        $message = '[<info>'.substr($tag, 0, 4).'</info>] '.$message;

        return $this->output->writeln($message, OutputInterface::VERBOSITY_VERBOSE);
    }

    public function veryVerbose($message, $tag)
    {
        $message = '[<info>'.substr($tag, 0, 4).'</info>] '.$message;

        return $this->output->writeln($message, OutputInterface::VERBOSITY_VERY_VERBOSE);
    }

    public function debug($message, $tag)
    {
        $message = '[<info>'.substr($tag, 0, 4).'</info>] '.$message;

        return $this->output->writeln($message, OutputInterface::VERBOSITY_DEBUG);
    }
}
