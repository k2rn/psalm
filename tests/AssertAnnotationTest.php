<?php
namespace Psalm\Tests;

class AssertAnnotationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'implictAssertInstanceOfB' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    function assertInstanceOfB(A $var): void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
            'dropInReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert !falsy $_b
                     */
                    function myAssert($_b) : void {
                        if (!$_b) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s !== null);
                        return $s;
                    }',
            ],
            'sortOfReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert true $_b
                     */
                    function myAssert($_b) : void {
                        if ($_b !== true) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s !== null);
                        return $s;
                    }',
            ],
            'implictAssertInstanceOfInterface' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function bar() : void {}
                    }
                    interface I {
                        public function foo(): void;
                    }
                    class B extends A implements I {
                        public function foo(): void {}
                    }

                    function assertInstanceOfI(A $var): void {
                        if (!$var instanceof I) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfI($a);
                        $a->bar();
                        $a->foo();
                    }',
            ],
            'implicitAssertInstanceOfMultipleInterfaces' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function bar() : void {}
                    }
                    interface I1 {
                        public function foo1(): void;
                    }
                    interface I2 {
                        public function foo2(): void;
                    }
                    class B extends A implements I1, I2 {
                        public function foo1(): void {}
                        public function foo2(): void {}
                    }

                    function assertInstanceOfInterfaces(A $var): void {
                        if (!$var instanceof I1 || !$var instanceof I2) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfInterfaces($a);
                        $a->bar();
                        $a->foo1();
                    }',
            ],
            'implicitAssertInstanceOfBInClassMethod' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    class C {
                        private function assertInstanceOfB(A $var): void {
                            if (!$var instanceof B) {
                                throw new \Exception();
                            }
                        }

                        private function takesA(A $a): void {
                            $this->assertInstanceOfB($a);
                            $a->foo();
                        }
                    }',
            ],
            'implicitAssertPropertyNotNull' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function foo(): void {}
                    }

                    class B {
                        /** @var A|null */
                        public $a;

                        private function assertNotNullProperty(): void {
                            if (!$this->a) {
                                throw new \Exception();
                            }
                        }

                        public function takesA(A $a): void {
                            $this->assertNotNullProperty();
                            $a->foo();
                        }
                    }',
            ],
            'implicitAssertWithoutRedundantCondition' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $data
                     * @throws \Exception
                     */
                    function assertIsLongString($data): void {
                        if (!is_string($data)) {
                            throw new \Exception;
                        }
                        if (strlen($data) < 100) {
                            throw new \Exception;
                        }
                    }

                    /**
                     * @throws \Exception
                     */
                    function f(string $s): void {
                        assertIsLongString($s);
                    }',
            ],
            'assertInstanceOfBAnnotation' => [
                '<?php
                    namespace Bar;

                    class A {}
                    class B extends A {
                        public function foo(): void {}
                    }

                    /** @psalm-assert B $var */
                    function myAssertInstanceOfB(A $var): void {
                        if (!$var instanceof B) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        myAssertInstanceOfB($a);
                        $a->foo();
                    }',
            ],
            'assertIfTrueAnnotation' => [
                '<?php
                    namespace Bar;

                    /** @psalm-assert-if-true string $myVar */
                    function isValidString(?string $myVar) : bool {
                        return $myVar !== null && $myVar[0] === "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isValidString($myString)) {
                        echo "Ma chaine " . $myString;
                    }',
            ],
            'assertIfFalseAnnotation' => [
                '<?php
                    namespace Bar;

                    /** @psalm-assert-if-false string $myVar */
                    function isInvalidString(?string $myVar) : bool {
                        return $myVar === null || $myVar[0] !== "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isInvalidString($myString)) {
                        // do something
                    } else {
                        echo "Ma chaine " . $myString;
                    }',
            ],
            'assertServerVar' => [
                '<?php
                    namespace Bar;

                    /**
                     * @psalm-assert-if-true string $a
                     * @param mixed $a
                     */
                    function my_is_string($a) : bool
                    {
                        return is_string($a);
                    }

                    if (my_is_string($_SERVER["abc"])) {
                        $i = substr($_SERVER["abc"], 1, 2);
                    }',
            ],
            'dontBleedBadAssertVarIntoContext' => [
                '<?php
                    namespace Bar;

                    class A {
                        public function foo() : bool {
                            return (bool) rand(0, 1);
                        }
                        public function bar() : bool {
                            return (bool) rand(0, 1);
                        }
                    }

                    /**
                     * Asserts that a condition is false.
                     *
                     * @param bool   $condition
                     * @param string $message
                     *
                     * @psalm-assert false $actual
                     */
                    function assertFalse($condition, $message = "") : void {}

                    function takesA(A $a) : void {
                        assertFalse($a->foo());
                        assertFalse($a->bar());
                    }',
            ],
            'assertAllStrings' => [
                '<?php
                    /**
                     * @psalm-assert iterable<mixed,string> $i
                     *
                     * @param iterable<mixed,mixed> $i
                     */
                    function assertAllStrings(iterable $i): void {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($i as $s) {
                            if (!is_string($s)) {
                                throw new \UnexpectedValueException("");
                            }
                        }
                    }

                    function getArray(): array {
                        return [];
                    }

                    function getIterable(): iterable {
                        return [];
                    }

                    $array = getArray();
                    assertAllStrings($array);

                    $iterable = getIterable();
                    assertAllStrings($iterable);',
                [
                    '$array' => 'array<array-key, string>',
                    '$iterable' => 'iterable<mixed, string>',
                ],
            ],
            'assertStaticMethodIfFalse' => [
                '<?php
                    class StringUtility {
                        /**
                         * @psalm-assert-if-false !null $yStr
                         */
                        public static function isNull(?string $yStr): bool {
                            if ($yStr === null) {
                                return true;
                            }
                            return false;
                        }
                    }

                    function test(?string $in) : void {
                        $str = "test";
                        if(!StringUtility::isNull($in)) {
                            $str .= $in;
                        }
                    }',
            ],
            'assertStaticMethodIfTrue' => [
                '<?php
                    class StringUtility {
                        /**
                         * @psalm-assert-if-true !null $yStr
                         */
                        public static function isNotNull(?string $yStr): bool {
                            if ($yStr === null) {
                                return true;
                            }
                            return false;
                        }
                    }

                    function test(?string $in) : void {
                        $str = "test";
                        if(StringUtility::isNotNull($in)) {
                            $str .= $in;
                        }
                    }',
            ],
            'assertUnion' => [
                '<?php
                    class Foo{
                        public function bar() : void {}
                    }

                    /**
                     * @param mixed $b
                     * @psalm-assert int|Foo $b
                     */
                    function assertIntOrFoo($b) : void {
                        if (!is_int($b) && !(is_object($b) && $b instanceof Foo)) {
                            throw new \Exception("bad");
                        }
                    }

                    /** @psalm-suppress MixedAssignment */
                    $a = $_GET["a"];

                    assertIntOrFoo($a);

                    if (!is_int($a)) $a->bar();',
            ],
            'assertThisTypeIfTrue' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert-if-true FooType $this
                         */
                        public function isFoo() : bool {
                            return $this instanceof FooType;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        if ($t->isFoo()) {
                            $t->bar();
                        }
                    }'
            ],
            'assertThisTypeSwitchTrue' => [
                '<?php
                    class Type {
                        /**
                         * @psalm-assert-if-true FooType $this
                         */
                        public function isFoo() : bool {
                            return $this instanceof FooType;
                        }
                    }

                    class FooType extends Type {
                        public function bar(): void {}
                    }

                    function takesType(Type $t) : void {
                        switch (true) {
                            case $t->isFoo():
                                $t->bar();
                        }
                    }'
            ],
            'assertNotArray' => [
                '<?php
                    /**
                     * @param  mixed $value
                     * @psalm-assert !array $value
                     */
                    function myAssertNotArray($value) : void {}

                     /**
                     * @param  mixed $value
                     * @psalm-assert !iterable $value
                     */
                    function myAssertNotIterable($value) : void {}

                    /**
                     * @param  int|array $v
                     */
                    function takesIntOrArray($v) : int {
                        myAssertNotArray($v);
                        return $v;
                    }

                    /**
                     * @param  int|iterable $v
                     */
                    function takesIntOrIterable($v) : int {
                        myAssertNotIterable($v);
                        return $v;
                    }'
            ],
            'assertIfTrueOnProperty' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if ($this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-true !null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfFalseOnProperty' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if ($this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-false null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfTrueOnPropertyNegated' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if (!$this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-true null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertIfFalseOnPropertyNegated' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    class B {
                        private ?A $a = null;

                        public function bar() : void {
                            if (!$this->assertProperty()) {
                                $this->a->foo();
                            }
                        }

                        /**
                         * @psalm-assert-if-false !null $this->a
                         */
                        public function assertProperty() : bool {
                            return $this->a !== null;
                        }
                    }'
            ],
            'assertPropertyVisibleOutside' => [
                '<?php
                    class A {
                        public ?int $x = null;

                        public function maybeAssignX() : void {
                            if (rand(0, 0) == 0) {
                                $this->x = 0;
                            }
                        }

                        /**
                         * @psalm-assert !null $this->x
                         */
                        public function assertProperty() : void {
                            if (is_null($this->x)) {
                                throw new RuntimeException();
                            }
                        }
                    }

                    $a = new A();
                    $a->maybeAssignX();
                    $a->assertProperty();
                    echo (2 * $a->x);',
            ],
            'parseAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array<string, string[]> $data
                     * @param mixed $data
                     */
                    function isArrayOfStrings($data): void {}

                    function foo(array $arr) : void {
                        isArrayOfStrings($arr);
                        foreach ($arr as $a) {
                            foreach ($a as $b) {
                                echo $b;
                            }
                        }
                    }'
            ],
            'noExceptionOnShortArrayAssertion' => [
                '<?php
                    /**
                     * @param mixed[] $a
                     */
                    function one(array $a): void {
                      isInts($a);
                    }

                    /**
                     * @psalm-assert int[] $value
                     * @param mixed $value
                     */
                    function isInts($value): void {}',
            ],
            'simpleArrayAssertion' => [
                '<?php
                    /**
                     * @psalm-assert array $data
                     * @param mixed $data
                     */
                    function isArray($data): void {}

                    /**
                     * @param iterable<string> $arr
                     * @return array<string>
                     */
                    function foo(iterable $arr) : array {
                        isArray($arr);
                        return $arr;
                    }'
            ],
            'listAssertion' => [
                '<?php
                    /**
                     * @psalm-assert list $data
                     * @param mixed $data
                     */
                    function isList($data): void {}

                    /**
                     * @param array<string> $arr
                     * @return list<string>
                     */
                    function foo(array $arr) : array {
                        isList($arr);
                        return $arr;
                    }'
            ],
            'scanAssertionTypes' => [
                '<?php
                    /**
                     * @param mixed $_p
                     * @psalm-assert-if-true Exception $_p
                     * @psalm-assert-if-false Error $_p
                     * @psalm-assert Throwable $_p
                     */
                    function f($_p): bool {
                        return true;
                    }

                    $q = null;
                    if (rand(0, 1) && f($q)) {}
                    if (!f($q)) {}'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'assertInstanceOfMultipleInterfaces' => [
                '<?php
                    class A {
                        public function bar() : void {}
                    }
                    interface I1 {
                        public function foo1(): void;
                    }
                    interface I2 {
                        public function foo2(): void;
                    }
                    class B extends A implements I1, I2 {
                        public function foo1(): void {}
                        public function foo2(): void {}
                    }

                    function assertInstanceOfInterfaces(A $var): void {
                        if (!$var instanceof I1 && !$var instanceof I2) {
                            throw new \Exception();
                        }
                    }

                    function takesA(A $a): void {
                        assertInstanceOfInterfaces($a);
                        $a->bar();
                        $a->foo1();
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'assertIfTrueNoAnnotation' => [
                '<?php
                    function isValidString(?string $myVar) : bool {
                        return $myVar !== null && $myVar[0] === "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isValidString($myString)) {
                        echo "Ma chaine " . $myString;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'assertIfFalseNoAnnotation' => [
                '<?php
                    function isInvalidString(?string $myVar) : bool {
                        return $myVar === null || $myVar[0] !== "a";
                    }

                    $myString = rand(0, 1) ? "abacus" : null;

                    if (isInvalidString($myString)) {
                        // do something
                    } else {
                        echo "Ma chaine " . $myString;
                    }',
                'error_message' => 'PossiblyNullOperand',
            ],
            'assertIfTrueMethodCall' => [
                '<?php
                    class C {
                        /**
                         * @param mixed $p
                         * @psalm-assert-if-true int $p
                         */
                        public function isInt($p): bool {
                            return is_int($p);
                        }
                        /**
                         * @param mixed $p
                         */
                        public function doWork($p): void {
                            if ($this->isInt($p)) {
                                strlen($p);
                            }
                        }
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'assertIfStaticTrueMethodCall' => [
                '<?php
                    class C {
                        /**
                         * @param mixed $p
                         * @psalm-assert-if-true int $p
                         */
                        public static function isInt($p): bool {
                            return is_int($p);
                        }
                        /**
                         * @param mixed $p
                         */
                        public function doWork($p): void {
                            if ($this->isInt($p)) {
                                strlen($p);
                            }
                        }
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'noFatalForUnknownAssertClass' => [
                '<?php
                    interface Foo {}

                    class Bar implements Foo {
                        public function sayHello(): void {
                            echo "Hello";
                        }
                    }

                    /**
                     * @param mixed $value
                     * @param class-string $type
                     * @psalm-assert SomeUndefinedClass $value
                     */
                    function assertInstanceOf($value, string $type): void {
                        // some code
                    }

                    // Returns concreate implementation of Foo, which in this case is Bar
                    function getImplementationOfFoo(): Foo {
                        return new Bar();
                    }

                    $bar = getImplementationOfFoo();
                    assertInstanceOf($bar, Bar::class);

                    $bar->sayHello();',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'assertValueImpossible' => [
                '<?php
                    /**
                     * @psalm-assert "foo"|"bar"|"foo-bar" $s
                     */
                    function assertFooBar(string $s) : void {
                    }

                    $a = "";
                    assertFooBar($a);',
                'error_message' => 'InvalidDocblock',
            ],
            'sortOfReplacementForAssert' => [
                '<?php
                    namespace Bar;

                    /**
                     * @param mixed $_b
                     * @psalm-assert true $_b
                     */
                    function myAssert($_b) : void {
                        if ($_b !== true) {
                            throw new \Exception("bad");
                        }
                    }

                    function bar(?string $s) : string {
                        myAssert($s);
                        return $s;
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
        ];
    }
}
