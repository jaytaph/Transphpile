<?php

namespace Phpile\Command;

use Phpile\Exception\FinderException;
use Phpile\Finder;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Transpile extends Command
{
    /** @var \Iterator */
    protected $sources;

    /** @var string Destination directory for files */
    protected $destination;

    /** @var bool Should do inplace changes */
    protected $inplace = false;

    /** @var bool Should output be done directly to stdout */
    protected $stdout = false;

    protected function configure()
    {
        $this
            ->setName('transpile')
            ->setDescription('Transpile PHP 7 code to a previous version')

            ->addArgument('source', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Array of files to transpile')

            ->addOption('no-recursion', '', InputOption::VALUE_NONE, 'Do not recursively seek directories')
            ->addOption('inplace', '', InputOption::VALUE_NONE, 'Do an in-place compilation (destroys source files!)')
            ->addOption('dest', 'd', InputOption::VALUE_REQUIRED, 'Compile files into this directory (defaults to "./php53")')
            ->addOption('stdout', '', InputOPtion::VALUE_NONE, 'Output to stdout instead of file')
        ;
    }

    /**
     * Do sanity checks on the arguments and options, and.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Check inplace and dest mutual exclusivity
        if ($this->getIo()->getOption('inplace') && $this->getIo()->getOption('dest')) {
            throw new InvalidOptionException(sprintf('Using both --dest and --inplace does not make sense.'));
        }

        // Check stdout mutual exclusivity
        if (($this->getIo()->getOption('inplace') || $this->getIo()->getOption('dest')) && $this->getIo()->getOption('stdout')) {
            throw new InvalidOptionException(sprintf('Using both --dest or --inplace together with --stdout does not make sense.'));
        }

        // Check destination and set to '.' as default
        $this->destination = $this->getIo()->getOption('dest');
        if (!$this->destination) {
            $this->destination = './php53';
        }
        if (!is_dir($this->destination) || !is_writeable($this->destination)) {
            @mkdir($this->destination);
            if (!is_dir($this->destination) || !is_writeable($this->destination)) {
                throw new InvalidOptionException(sprintf('Destination directory "%s" does not exist or is not writable or could not be created.', $this->destination));
            }
        }

        // Find sources
        try {
            $finder = new Finder($input->getArgument('source'), $this->getIo());
            $this->sources = $finder->find();
        } catch (FinderException $e) {
            throw new InvalidArgumentException(sprintf("Cannot find/read '%s'", $e->getPath()));
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getIo()->verbose('Starting', 'main');

        $transpiler = new \Phpile\Transpile\Transpile($this->getIo());
        foreach ($this->sources as $source) {
            $destination = $this->generateDestination($source);

            $this->getIo()->verbose('Transpiling <comment>'.$source.'</comment> to <comment>'.$destination.'</comment>', 'trns');
            $transpiler->transpile($source, $destination);
        }

        $this->getIo()->verbose('All done', 'main');
    }

    /**
     * @param $source
     *
     * @return string
     */
    protected function generateDestination($source)
    {
        // Inplace means the destination is the same as the source
        if ($this->inplace) {
            return $source;
        }

        // When using stdout, use the '-' to reflect this
        if ($this->getIo()->getOption('stdout')) {
            return '-';
        }

        // Otherwise, use the destination path and the source
        return $this->destination.'/'.$source;
    }
}
