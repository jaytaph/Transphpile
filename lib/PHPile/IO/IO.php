<?php

namespace PHPile\IO;

trait IO
{
    protected $io;

    /**
     * @return mixed
     */
    public function getIO()
    {
        return $this->io;
    }

    /**
     * @param IOInterface $io
     */
    public function setIO(IOInterface $io)
    {
        $this->io = $io;
    }
}
