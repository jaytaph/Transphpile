<?php

namespace PHPile;

use PHPile\Exception\FinderException;
use PHPile\IO\IOInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Finder
{
    use IO\IO;

    /**
     * @var array
     */
    protected $sources;

    public function __construct(array $sources, IOInterface $io)
    {
        $this->setIO($io);

        $this->sources = $sources;
    }

    /**
     * @return SymfonyFinder
     */
    public function find()
    {
        $finder = SymfonyFinder::create()
            ->files()
            ->name('*.php')
        ;

        foreach ($this->getIO()->getArgument('source') as $source) {
            if (is_dir($source) && is_readable($source)) {
                $this->getIO()->veryVerbose(sprintf("Adding directory %s<info>$source</info>", $this->getIO()->getOption('no-recursion') ? 'recursively ' : ''), 'find');
                $dirFinder = SymfonyFinder::create()
                    ->in($source)
                    ->files()
                    ->name('*.php')
                ;

                if ($this->getIO()->getOption('no-recursion') == true) {
                    $dirFinder->depth(0);
                }

                $it = $dirFinder;
            } elseif (is_file($source) && is_readable($source)) {
                $this->getIO()->veryVerbose("Adding file <info>$source</info>", 'find');
                $it = array($source);
            } else {
                $e = new FinderException();
                $e->setPath($source);
                throw $e;
            }

            // Add finder iterator, or array with files to the main finder
            $finder->append($it);
        }

        return $finder;
    }
}
