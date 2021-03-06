<?php

declare(strict_types=1);

namespace RoaveTest\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased;

use PHPUnit\Framework\TestCase;
use Roave\BackwardCompatibility\Change;
use Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\AncestorRemoved;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\StringSourceLocator;
use function array_combine;
use function array_keys;
use function array_map;
use function iterator_to_array;

/**
 * @covers \Roave\BackwardCompatibility\DetectChanges\BCBreak\InterfaceBased\AncestorRemoved
 */
final class AncestorRemovedTest extends TestCase
{
    /**
     * @dataProvider interfacesToBeTested
     *
     * @param string[] $expectedMessages
     */
    public function testDiffs(
        ReflectionClass $fromInterface,
        ReflectionClass $toInterace,
        array $expectedMessages
    ) : void {
        $changes = (new AncestorRemoved())
            ->__invoke($fromInterface, $toInterace);

        self::assertSame(
            $expectedMessages,
            array_map(function (Change $change) : string {
                return $change->__toString();
            }, iterator_to_array($changes))
        );
    }

    /** @return (string[]|ReflectionClass)[][] */
    public function interfacesToBeTested() : array
    {
        $locator       = (new BetterReflection())->astLocator();
        $fromReflector = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface IA {}
interface IB extends IA {}
interface IC extends IB {}
interface ID {}
interface ParentInterfaceAdded {}
interface ParentInterfaceRemoved extends IA {}
interface ParentInterfaceIndirectlyRemoved extends IB {}
interface ParentInterfaceVeryIndirectlyRemoved extends IC {}
interface ParentInterfaceOrderSwapped extends IA, ID {}
PHP
            ,
            $locator
        ));
        $toReflector   = new ClassReflector(new StringSourceLocator(
            <<<'PHP'
<?php

interface IA {}
interface IB {}
interface IC extends IB {}
interface ID {}
interface ParentInterfaceAdded extends IA {}
interface ParentInterfaceRemoved {}
interface ParentInterfaceIndirectlyRemoved extends IB {}
interface ParentInterfaceVeryIndirectlyRemoved extends IC {}
interface ParentInterfaceOrderSwapped extends ID, IA {}
PHP
            ,
            $locator
        ));

        $interfaces = [
            'IA' => [],
            'IB' => ['[BC] REMOVED: These ancestors of IB have been removed: ["IA"]'],
            'IC' => ['[BC] REMOVED: These ancestors of IC have been removed: ["IA"]'],
            'ParentInterfaceAdded' => [],
            'ParentInterfaceRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceRemoved have been removed: ["IA"]'],
            'ParentInterfaceIndirectlyRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceIndirectlyRemoved have been removed: ["IA"]'],
            'ParentInterfaceVeryIndirectlyRemoved' => ['[BC] REMOVED: These ancestors of ParentInterfaceVeryIndirectlyRemoved have been removed: ["IA"]'],
            'ParentInterfaceOrderSwapped' => [],
        ];

        return array_combine(
            array_keys($interfaces),
            array_map(
                function (string $interfaceName, array $errors) use ($fromReflector, $toReflector) : array {
                    return [
                        $fromReflector->reflect($interfaceName),
                        $toReflector->reflect($interfaceName),
                        $errors,
                    ];
                },
                array_keys($interfaces),
                $interfaces
            )
        );
    }
}
