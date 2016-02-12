<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use Transphpile\Transpile\NodeStateStack;

/*
 * Keeps track in which function we currently reside, which typehint it returns and will add
 * checks on parameters when in strict mode.
 */

class FunctionVisitor extends NodeVisitorAbstract
{
    /**
     * Store node function.
     *
     * @param Node $node
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Node\FunctionLike) {
            NodeStateStack::getInstance()->push('currentFunction', $node);
        }

        if ($node instanceof ClassLike) {
            NodeStateStack::getInstance()->push('currentClass', $node);
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassLike) {
            NodeStateStack::getInstance()->pop('currentClass');
        }

        if (!$node instanceof Node\FunctionLike) {
            return;
        }

        $functionNode = NodeStateStack::getInstance()->end('currentFunction');
        NodeStateStack::getInstance()->pop('currentFunction');

        // Remove return type if set
        if ($node->returnType) {
            $node->returnType = null;
        }

        // Remove scalar types and store for later
        $params = array();
        foreach ($node->params as $param) {
            if (in_array($param->type, array('string', 'int', 'float', 'bool'))) {
                $canBeNull = false;
                if ($param->default != null) {
                    if ($param->default instanceof Node\Expr\ConstFetch) {
                        if ($param->default->name->parts[0] == "null") {
                            $canBeNull = true;
                        }
                    }
                }

                $params[] = array(
                    'type' => $param->type,
                    'arg' => $param->name,
                    'func' => $node->name,
                    'nullable' => $canBeNull,
                );
                $param->type = null;
            }
        }

        // Don't add checks when we don't enforce strict
        if (! NodeStateStack::getInstance()->get('isStrict')) {
            return null;
        }

        // No typehinting on abstract
        if ($functionNode instanceOf Node\Stmt\ClassMethod && $functionNode->isAbstract()) {
            return null;
        }

        // No typehinting on interfaces
        if (NodeStateStack::getInstance()->end('currentClass') instanceof Interface_) {
            return null;
        }

        // Add code for checking scalar types
        foreach ($params as $param) {
            $code = sprintf(
                '<?php if (! is_%s($%s) %s) { throw new \InvalidArgumentException("Argument \$%s passed to %s() must be of the type %s, ".get_class($%s)." given"); }',
                $param['type'], $param['arg'],
                ($param['nullable'] ? 'and ! is_null($'.$param['arg'].')' : ""),
                $param['arg'], $param['func'], $param['type'], $param['arg']
            );

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $stmts = $parser->parse($code);

            if (! $node->stmts) {
                $node->stmts = $stmts;
            } else {
                $node->stmts = array_merge($stmts, $node->stmts);
            }
        }
    }
}
