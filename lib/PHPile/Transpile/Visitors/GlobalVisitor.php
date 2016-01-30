<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/*
 * Does some global magic
 */

class GlobalVisitor extends NodeVisitorAbstract
{

    public function enterNode(Node $node)
    {
    }

    public function leaveNode(Node $node)
    {
    }

}
