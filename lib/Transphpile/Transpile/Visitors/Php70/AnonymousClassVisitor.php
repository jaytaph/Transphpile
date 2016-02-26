<?php

namespace Transphpile\Transpile\Visitors\Php70;

use Transphpile\Transpile\Transpile;
use Transphpile\Transpile\NodeStateStack;
use PhpParser\Comment;
use PhpParser\Node;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;

/*
 * Convert anonymous classes
 *
 */

class AnonymousClassVisitor extends NodeVisitorAbstract
{

    public function leaveNode(Node $node)
    {
        // we need to check on "new", as we need to replace "new class" instead of just the "class"
        if (!$node instanceof Node\Expr\New_) {
            return null;
        }

        // Make sure the we are dealing with an anonymous class
        $classNode = $node->class;
        if (! $classNode instanceof Node\Stmt\Class_) {
            return null;
        }

        // Generate (un)anonymous class name
        $anonClass = "anonClass_".uniqid();

        // Make the class unanonmyous
        $classNode->name = $anonClass;

        // Store the class so we add them later on to the code
        NodeStateStack::getInstance()->push('anonClasses', $classNode);

        // Generate new code that instantiate our new class
        $newNode = new Node\Expr\New_(
            new Node\Expr\ConstFetch(
                new Node\Name($anonClass)
            )
        );

        return $newNode;
    }

    /**
     * Add final anonymous classes to the statements
     *
     * @param array $nodes
     * @return array
     */
    public function afterTraverse(array $nodes)
    {
        // Nothing to do when there are no anonymous classes
        if (NodeStateStack::getInstance()->count('anonClasses') == 0) {
            return $nodes;
        }

        // We must transpile anonymous classes first, as we haven't done this yet
        $traverser = Transpile::getTraverser();
        $anonClassStmts = $traverser->traverse(NodeStateStack::getInstance()->get('anonClasses'));

        // Find hook point for anonymous classes, which must be after declare, namespaces and use-statements, and can
        // before anything else. This might cause issues when anonymous classes implement interfaces that are defined
        // later on.
        $idx = $this->getAnonymousClassHookIndex($nodes);

        // Array_merge the anonymous class statements on the correct position
        $preStmts = array_slice($nodes, 0, $idx);
        $postStmts = array_slice($nodes, $idx);
        $nodes = array_merge($preStmts, $anonClassStmts, $postStmts);

        return $nodes;
    }

    /**
     * Find the index of the first statement that is not a declare, use or namespace statement.
     *
     * @param array $stmts
     * @return int
     */
    protected function getAnonymousClassHookIndex(array $stmts)
    {
        // Find the first statement that is not a declare, namespace or use-statement
        $ret = false;
        foreach ($stmts as $idx => $stmt) {
            if (! $stmt instanceof Declare_ &&
                ! $stmt instanceof Use_ &&
                ! $stmt instanceof Namespace_) {
                $ret = $idx;
                break;
            }
        }

        if ($ret !== false) {
            return $ret;
        }

        // Seems this file only consist fo declares, use and namespaces.. That should not happen
        throw new \RuntimeException("Cannot find an location to insert anonymous classes");
    }

}
