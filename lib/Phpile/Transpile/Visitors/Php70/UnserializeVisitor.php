<?php

namespace Phpile\Transpile\Visitors\Php70;

use Phpile\Transpile\Exception\TranspileException;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/*
 * converts unserialize($foo, array('allowed_classes' => ... ) into
 *
 *    phpile\unserialize::unserializer($foo, array('allowed_classes')
 *
 * but ONLY when "allowed_classes => true" or an array
 *
 */

class UnserializeVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        // Trigger on function call "unserialize"
        if (!$node instanceof Node\Expr\FuncCall || $node->name != "unserialize") {
            return null;
        }

        // Continue only when unserialize() is used with two or more arguments
        if (count($node->args) < 2) {
            return null;
        }

        // Assume OptionsNode is always an array
        $optionsNode = $node->args[1]->value;


        // Setup unserializer node
        $unserializeNode = new Node\Expr\StaticCall(
            new Node\Name('\phpile\unserializer'),
            new Node\Name('unserialize'),
            $node->args
        );

        foreach ($optionsNode->items as $itemNode) {
            if ($itemNode->key->value == "allowed_classes") {
                $valueNode = $itemNode->value;

                if ($valueNode instanceOf Node\Expr\ConstFetch) {
                    $value = $valueNode->name->parts[0];

                    if ($value == "false") {
                        // allowed_classes = false, so no classes are allowed
                        return $unserializeNode;
                    }

                    if ($value == "true") {
                        // allowed_classes = true, so we can remove the options safely
                        array_pop($node->args);
                        return $node;
                    }
                } elseif ($valueNode instanceOf Node\Expr\Array_) {
                    // Array keeps a list of classes that may be initialized
                    return $unserializeNode;
                }
            }
        }

    }
}
