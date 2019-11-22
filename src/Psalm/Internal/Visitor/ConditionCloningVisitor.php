<?php
declare(strict_types=1);
namespace Psalm\Internal\Visitor;

use function array_map;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class ConditionCloningVisitor extends NodeVisitorAbstract
{
    public function enterNode(Node $origNode)
    {
        /** @var \PhpParser\Node\Expr $origNode */
        $node = clone $origNode;

        $node_type = \Psalm\Type\Provider::getNodeType($origNode);

        if ($node_type) {
            \Psalm\Type\Provider::setNodeType($node, clone $node_type);
        }

        return $node;
    }
}
