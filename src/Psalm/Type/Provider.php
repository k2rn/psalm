<?php

namespace Psalm\Type;

use PhpParser;
use function spl_object_id;

class Provider
{
    /** @var array<int, Union> */
    private static $node_types = [];

    /** @var array<int, array<string, non-empty-list<non-empty-list<string>>>> */
    private static $node_assertions = [];

    /** @var array<int, array<int, \Psalm\Storage\Assertion>> */
    private static $node_if_true_assertions = [];

    /** @var array<int, array<int, \Psalm\Storage\Assertion>> */
    private static $node_if_false_assertions = [];

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public static function setNodeType($node, Union $type) : void
    {
        self::$node_types[spl_object_id($node)] = $type;
    }

    /**
     * @param PhpParser\Node\Expr|PhpParser\Node\Name|PhpParser\Node\Stmt\Return_ $node
     */
    public static function getNodeType($node) : ?Union
    {
        return self::$node_types[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr $node
     * @param array<string, non-empty-list<non-empty-list<string>>>|null $assertions
     */
    public static function setNodeAssertions($node, ?array $assertions) : void
    {
        self::$node_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr $node
     * @return array<string, non-empty-list<non-empty-list<string>>>|null
     */
    public static function getNodeAssertions($node) : ?array
    {
        return self::$node_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public static function setNodeIfTrueAssertions($node, array $assertions) : void
    {
        self::$node_if_true_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public static function getNodeIfTrueAssertions($node) : ?array
    {
        return self::$node_if_true_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @param array<int, \Psalm\Storage\Assertion> $assertions
     */
    public static function setNodeIfFalseAssertions($node, array $assertions) : void
    {
        self::$node_if_false_assertions[spl_object_id($node)] = $assertions;
    }

    /**
     * @param PhpParser\Node\Expr\FuncCall|PhpParser\Node\Expr\MethodCall|PhpParser\Node\Expr\StaticCall $node
     * @return array<int, \Psalm\Storage\Assertion>|null
     */
    public static function getNodeIfFalseAssertions($node) : ?array
    {
        return self::$node_if_false_assertions[spl_object_id($node)] ?? null;
    }

    /**
     * @param PhpParser\Node\Expr $node
     */
    public static function isPureCompatible($node) : bool
    {
        $node_type = self::getNodeType($node);

        return ($node_type && $node_type->external_mutation_free) || isset($node->pure);
    }

    /**
     * @param PhpParser\Node\Expr $node
     */
    public static function clearNodeOfTypeAndAssertions($node) : void
    {
        $id = spl_object_id($node);

        unset(self::$node_types[$id], self::$node_assertions[$id]);
    }

    public static function reset() : void
    {
        self::$node_types = [];
        self::$node_assertions = [];
        self::$node_if_true_assertions = [];
        self::$node_if_false_assertions = [];
    }
}
