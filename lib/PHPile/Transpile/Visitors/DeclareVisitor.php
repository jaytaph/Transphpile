<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

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

            // strict_types should only use 0 or 1, but check for LNumber to be safe
            if ($declare->value instanceof Node\Scalar\LNumber) {
                global $is_strict;
                $is_strict = ($declare->value->value == 1);
            }

            // Remove strict_type declares
            return NodeTraverser::REMOVE_NODE;
        }

        return null;
    }
}
