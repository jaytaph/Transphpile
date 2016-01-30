<?php

namespace PHPile\Transpile\Visitors;

use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/*
 * Convert anonymous classes
 *
 */

class AnonymousClassVisitor extends NodeVisitorAbstract
{

    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\New_) {
            return null;
        }

        $classNode = $node->class;
        if (! $classNode instanceof Node\Stmt\Class_) {
            return null;
        }

        $anonClass = "anonClass_".uniqid();

        $classNode->name = $anonClass;

        global $anonClasses;
        $anonClasses[] = $classNode;

        $newNode = new Node\Expr\New_(
            new Node\Expr\ConstFetch(
                new Node\Name($anonClass)
            )
        );

        return $newNode;
    }

}
