<?php

namespace Transphpile\Console\Command;

use Transphpile\Console\Application;
use Transphpile\IO\IOInterface;
use Transphpile\IO\NullIO;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand
{
    /** @var IOInterface */
    protected $io;

    /**
     * @return IOInterface
     */
    public function getIo()
    {
        if ($this->io == null) {
            $app = $this->getApplication();
            $this->io = ($app instanceof Application) ? $app->getIO() : new NullIO();
        }

        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    public function setIo(IOInterface $io)
    {
        $this->io = $io;
    }
}
