<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transphpile\Transpile\NodeStateStack;

/*
 * Removes declare('strict_type') statements, and keep references if we are running strict_type or not for this file
 */

class DeclareVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (! $node instanceof Node\Stmt\Declare_) {
            return null;
        }

        foreach ($node->declares as $idx => $declare) {
            if ($declare->key != 'strict_types') {
                continue;
            }

            // Set global strict value so others know what to do with scalar typehinting and return types.
            NodeStateStack::getInstance()->isStrict = ($declare->value->value == 1);

            // Remove the strict_type declare
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
