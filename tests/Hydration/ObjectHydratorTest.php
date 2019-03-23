<?php declare(strict_types=1);

namespace FatCode\Tests\Storage\Hydration;

use FatCode\Storage\Exception\SchemaException;
use FatCode\Storage\Hydration\Instantiator;
use FatCode\Storage\Hydration\ObjectHydrator;
use FatCode\Storage\Hydration\Schema;
use FatCode\Storage\Hydration\SchemaLoader;
use FatCode\Tests\Storage\Fixtures\User;
use FatCode\Tests\Storage\Fixtures\UserName;
use FatCode\Tests\Storage\Fixtures\UserSchema;
use FatCode\Tests\Storage\Fixtures\UserWallet;
use PHPUnit\Framework\TestCase;

final class ObjectHydratorTest extends TestCase
{
    public function testRegisterSchema() : void
    {
        $schema = new UserSchema();
        $objectHydrator = new ObjectHydrator();
        $objectHydrator->addSchema($schema);

        self::assertSame($schema, $objectHydrator->getSchema($schema->getTargetClass()));
    }

    public function testRegisterLoader() : void
    {
        $objectHydrator = new ObjectHydrator();
        self::assertFalse($objectHydrator->hasSchema(User::class));

        $loader = new class implements SchemaLoader {
            public function load(string $class): ?Schema
            {
                if ($class === User::class) {
                    return new UserSchema();
                }

                return null;
            }
        };
        $objectHydrator->addSchemaLoader($loader);
        self::assertTrue($objectHydrator->hasSchema(User::class));
        self::assertInstanceOf(UserSchema::class, $objectHydrator->getSchema(User::class));
    }

    public function testFailGetOnUndefinedSchema() : void
    {
        $this->expectException(SchemaException::class);
        $objectHydrator = new ObjectHydrator();
        $objectHydrator->getSchema('Something');
    }

    public function testHydrate() : void
    {
        $objectHydrator = new ObjectHydrator();
        $objectHydrator->addSchema(new UserSchema());

        $user = $objectHydrator->hydrate(
            [
                'name' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                ],
                'wallet' => [
                    'amount' => '1000.20',
                    'currency' => 'EUR',
                ],
                'age' => '15'
            ],
            Instantiator::instantiate(User::class)
        );

        self::assertInstanceOf(User::class, $user);
        self::assertSame(15, $user->getAge());
        self::assertInstanceOf(UserWallet::class, $user->getWallet());
        self::assertSame('1000.20', $user->getWallet()->getAmount());
        self::assertSame('EUR', $user->getWallet()->getCurrency());
        self::assertInstanceOf(UserName::class, $user->getName());
        self::assertSame('John', $user->getName()->getFirstName());
        self::assertSame('Doe', $user->getName()->getLastName());
    }
}
