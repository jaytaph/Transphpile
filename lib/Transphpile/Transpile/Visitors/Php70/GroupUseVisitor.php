<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitorAbstract;
use Transphpile\Transpile\Exception\TranspileException;

/*
 * converts  "use foo\{bar,baz}" into:
 *
 *   use foo\bar;
 *   use foo\baz;
 */

class GroupUseVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Stmt\GroupUse) {
            return null;
        }

        $useNodes = array();
        foreach ($node->uses as $use) {
            // Create complete (F)QCN
            $name = new Node\Name(
                array_merge($node->prefix->parts, array($use->name))
            );

            // Create a new namespace for this specific import
            $useNode = new Node\Stmt\Use_(
                array(new Node\Stmt\UseUse(
                    $name,
                    // Don't add an alias when the alias is the same as the name
                    ($use->alias == $use->name) ? null : $use->alias
                )),
                $node->type
            );

            $useNodes[] = $useNode;
        }


        return $useNodes;
    }
}
