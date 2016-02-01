<?php

namespace Phpile\Transpile\Exception;

use PhpParser\Node;

class NodeException extends \RuntimeException {

    protected $node;

    public function setNode(Node $node)
    {
        $this->node = $node;
    }

    /**
     * @return mixed
     */
    public function getNode()
    {
        return $this->node;
    }

}
