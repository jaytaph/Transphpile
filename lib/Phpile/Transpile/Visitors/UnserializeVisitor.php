<?php

namespace Phpile\Transpile\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/*
 *
 */

class UnserializeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if (!$node instanceof Node\Expr\FuncCall || $node->name != "unserialize") {
            return null;
        }

        if (count($node->args) < 2) {
            return null;
        }

        $optionsNode = $node->args[1]->value;

        // Options is always an array

        foreach ($optionsNode->items as $itemNode) {
            if ($itemNode->key->value == "allowed_classes") {
                $valueNode = $itemNode->value;

                if ($valueNode instanceOf Node\Expr\ConstFetch) {
                    $value = $valueNode->name->parts[0];

                    if ($value == "false") {
                        // allowed_classes = false, so no classes are allowed
                        print "AC = false\n";
                    }

                    if ($value == "true") {
                        // allowed_classes = true, so we can remove the options safely
                        print "AC = true\n";
                        array_pop($node->args);
                        return $node;
                    }
                } elseif ($valueNode instanceOf Node\Expr\Array_) {
                    // Array keeps a list of classes that may be initialized
                    print "AC = array\n";
                }
            }
        }

    }
}
