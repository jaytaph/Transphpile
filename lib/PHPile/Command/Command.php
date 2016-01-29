<?php

namespace PHPile\Command;

use PHPile\Console\Application;
use PHPile\IO\IOInterface;
use PHPile\IO\NullIO;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Command extends SymfonyCommand {

    /** @var IOInterface */
    protected $io;

    /**
     * @return IOInterface
     */
    function getIo() {
        if ($this->io == null) {
            $app = $this->getApplication();
            $this->io = ($app instanceOf Application) ? $app->getIO() : new NullIO();
        }

        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    function setIo(IOInterface $io) {
        $this->io = $io;
    }

}
