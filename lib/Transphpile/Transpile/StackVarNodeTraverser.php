<?php

namespace Transphpile\Transpile;

use PhpParser\NodeTraverser;

/*
 * Small decorator that will push and pop variables per node traversal, as we will traverse subnodes manually (for
 * instance, when encountering anonymous classes). We must save / use our variables per traversal.
 */
class StackVarNodeTraverser extends NodeTraverser
{

    /**
     * @param array $nodes
     * @return \PhpParser\Node[]
     */
    public function traverse(array $nodes)
    {
        NodeStateStack::getInstance()->pushVars();
        $ret = parent::traverse($nodes);
        NodeStateStack::getInstance()->popVars();

        return $ret;
    }

}

