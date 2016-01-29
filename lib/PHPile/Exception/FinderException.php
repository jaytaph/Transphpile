<?php

namespace PHPile\Exception;

class FinderException extends \RuntimeException {

    protected $path;

    function setPath($path) {
        $this->path = $path;
    }

    function getPath() {
        return $this->path;
    }

}
