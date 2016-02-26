<?php

namespace Transphpile\Transpile\Visitors\Php70;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use Transphpile\Transpile\NodeStateStack;

class GeneratorReturnVisitor extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\Yield_) {
            $functionNode = NodeStateStack::getInstance()->pop('currentFunction');
            $functionNode['generator'] = true;
            NodeStateStack::getInstance()->push('currentFunction', $functionNode);

            return null;
        }

        if ($node instanceof Node\Stmt\Return_) {
            $functionNode = NodeStateStack::getInstance()->end('currentFunction');

            if ($functionNode['generator']) {
                throw new TranspileException("Cannot transpile return statements from generators");
            }

        }

        return null;
    }
}
