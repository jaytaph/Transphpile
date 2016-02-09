<?php

namespace Transphpile\Exception;

class FinderException extends \RuntimeException
{
    protected $path;

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
