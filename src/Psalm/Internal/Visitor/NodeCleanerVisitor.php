<?php
namespace Psalm\Internal\Visitor;

use PhpParser;

/**
 * @internal
 */
class NodeCleanerVisitor extends PhpParser\NodeVisitorAbstract implements PhpParser\NodeVisitor
{
    /**
     * @param  PhpParser\Node $node
     *
     * @return null|int
     */
    public function enterNode(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Expr) {
            \Psalm\Type\Provider::clearNodeOfTypeAndAssertions($node);
        }

        return null;
    }
}
