<?php

namespace PHPile\IO;

class NullIO implements IOInterface {

    public function getOption($option)
    {
        return false;
    }

    public function getOptions()
    {
        return array();
    }

    public function getArgument($argument)
    {
        return false;
    }

    public function getArguments()
    {
        return array();
    }

    public function output($message)
    {
    }

    public function verbose($message)
    {
    }

    public function veryVerbose($message)
    {
    }

    public function debug($message)
    {
    }


}
