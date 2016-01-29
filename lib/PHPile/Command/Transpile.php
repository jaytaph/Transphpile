<?php

namespace PHPile\Command;

use PHPile\Exception\FinderException;
use PHPile\Finder;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Transpile extends Command {

    /** @var \Iterator */
    protected $sources;

    /** @var string Destination directory for files */
    protected $destination;

    /** @var bool Should do inplace changes */
    protected $inplace = false;

    /** @var string Target version of PHP to transpile to */
    protected $target;

    /** @var bool Should output be done directly to stdout */
    protected $stdout = false;


    protected function configure()
    {
        $this
            ->setName('transpile')
            ->setDescription('Transpile PHP 7 code to a previous version')

            ->addArgument('source', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Array of files to transpile')

            ->addOption('target', 't', InputOption::VALUE_REQUIRED, 'Set PHP target version to transpile to (defaults to 53)', '53')
            ->addOption('no-recursion', '', InputOption::VALUE_NONE, 'Do not recursively seek directories')
            ->addOption('inplace', '', InputOption::VALUE_NONE, 'Do an in-place compilation (destroys source files!)')
            ->addOption('dest', 'd', InputOption::VALUE_REQUIRED, 'Compile files into this directory (defaults to ".")')
            ->addOption('stdout', '', InputOPtion::VALUE_NONE, 'Output to stdout instead of file')
        ;
    }

    /**
     * Do sanity checks on the arguments and options, and
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        // Check target
        $this->target = $this->getIo()->getOption('target');
        if (! in_array($this->target, array('53', '54', '55', '56'))) {
            throw new InvalidOptionException(sprintf('Target must be either 53, 54, 55 or 56'));
        }

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
        if (! $this->destination) $this->destination = ".";
        if (! is_dir($this->destination) || ! is_writeable($this->destination)) {
            throw new InvalidOptionException(sprintf('Destination directory "%s" does not exist or is not writable', $this->destination));
        }

        // Find sources
        try {
            $finder = new Finder($input->getArgument('source'), $this->getIo());
            $this->sources = $finder->find();
        } catch (FinderException $e) {
            throw new InvalidArgumentException(sprintf("Cannot find/read '%s'", $e->getPath()));
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getIo()->output('Starting', 'main');

        $transpiler = new \PHPile\Transpile\Transpile($this->getIo());
        foreach ($this->sources as $source) {
            $dest = $this->generateDestination($source);

            $transpiler->transpile($source, $dest, $this->target);
        }

        $this->getIo()->output('All done', 'main');
    }


    /**
     * @param $source
     * @return string
     */
    protected function generateDestination($source) {
        if ($this->inplace) {
            return $source;
        }

        return $this->destination . '/' . $source;
    }


}
