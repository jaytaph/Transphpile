<?php

namespace Phpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Transpile\Exception\TranspileException;

/*
 * converts unserialize($foo, array('allowed_classes' => ... ) into
 *
 *    unserialize($foo)
 *
 * but ONLY when "allowed_classes => false"
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

        foreach ($optionsNode->items as $itemNode) {
            if ($itemNode->key->value == "allowed_classes") {
                $valueNode = $itemNode->value;

                if ($valueNode instanceOf Node\Expr\ConstFetch) {
                    $value = $valueNode->name->parts[0];

                    if ($value == "false") {
                        // allowed_classes = false, so no classes are allowed
                        $ex = new TranspileException("Cannot transpile unserialize() with allowed_classes = false");
                        $ex->setNode($node);
                        throw new $ex;
                    }

                    if ($value == "true") {
                        // allowed_classes = true, so we can remove the options safely
                        array_pop($node->args);
                        return $node;
                    }
                } elseif ($valueNode instanceOf Node\Expr\Array_) {
                    // Array keeps a list of classes that may be initialized

                    $ex = new TranspileException("Cannot transpile unserialize() with allowed_classes = array()");
                    $ex->setNode($node);
                    throw new $ex;
                }
            }
        }

    }
}
