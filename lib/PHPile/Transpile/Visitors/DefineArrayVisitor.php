<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/*
 * Converts define() arrays into const arrays
 */

class DefineArrayVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\FuncCall) {
            return null;
        }

        if ($node->name != 'define') {
            return null;
        }


        $nameNode = $node->args[0]->value;
        $valueNode = $node->args[1]->value;

        // We only convert arrays
        if (! $valueNode instanceof Node\Expr\Array_) {
            return null;
        }

        // Convert defined() array to const
        $constNode = new Node\Stmt\Const_(array(
            $constNode = new Node\Const_($nameNode->value, $valueNode)
        ));

        return $constNode;
    }

}
