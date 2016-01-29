<?php

namespace PHPile\IO;

trait IO {
    protected $io;

    /**
     * @return mixed
     */
    function getIO() {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    function setIO(IOInterface $io) {
        $this->io = $io;
    }
}
