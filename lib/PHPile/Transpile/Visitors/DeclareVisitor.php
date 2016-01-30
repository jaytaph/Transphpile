<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class DeclareVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Declare_) {
            foreach ($node->declares as $idx => $declare) {
                if ($declare->key == 'strict_types') {
                    return NodeTraverser::REMOVE_NODE;
                }

                if ($declare->value instanceof Node\Scalar\LNumber) {
                    $value = $declare->value->value;
                }
                if ($declare->value instanceof Node\Scalar\String_) {
                    $value = $declare->value->value;
                }
                if ($declare->value instanceof Node\Expr\ConstFetch) {
                    $value = $declare->value->name->parts[0];
                }
                echo 'DECLARE FOUND: '.$declare->key.' '.$value."\n";
            }

            return;
        }
    }
}
