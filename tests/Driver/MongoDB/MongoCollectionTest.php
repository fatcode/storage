<?php declare(strict_types=1);

namespace FatCode\Tests\Storage\Driver\MongoDB;

use FatCode\Storage\Driver\MongoDb\MongoCollection;
use FatCode\Storage\Exception\DriverException;
use FatCode\Tests\Storage\Driver\MongoDB\Command\DatabaseHelpers;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

final class MongoCollectionTest extends TestCase
{
    use DatabaseHelpers;

    public function testGet() : void
    {
        $id = new ObjectId();
        $this->generateUser(['_id' => $id]);
        $collection = new MongoCollection($this->getConnection(), 'users');
        $user = $collection->get($id);

        self::assertIsArray($user);
        self::assertEquals($id, $user['_id']);
    }

    public function testGetFail() : void
    {
        $collection = new MongoCollection($this->getConnection(), 'users');
        $user = $collection->get(new ObjectId());
        self::assertNull($user);
    }

    public function testInsert() : void
    {
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);

        self::assertTrue($success);
        $john = $collection->get($id);
        self::assertEquals($id, $john['_id']);
        self::assertSame('John', $john['name']);
        self::assertSame('Doe', $john['lastName']);
    }

    public function testInsertMany() : void
    {
        $this->createCollection('users');
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert(['name' => 'Bob'], ['name' => 'John'], ['name' => 'Tom']);

        self::assertTrue($success);
    }

    public function testInsertFail() : void
    {
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertTrue($success);
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertFalse($success);
    }

    public function testUpdate() : void
    {
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertTrue($success);
        $success = $collection->update(['_id' => $id, 'name' => 'Bob']);
        self::assertTrue($success);

        $bob = $collection->get($id);
        self::assertSame('Bob', $bob['name']);
    }

    public function testUpdateFailWithoutId() : void
    {
        $this->expectException(DriverException::class);
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertTrue($success);
        $collection->update(['name' => 'Bob']);
    }

    public function testUpsert() : void
    {
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->upsert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertTrue($success);

        $success = $collection->upsert(['_id' => $id, 'name' => 'Bob']);
        self::assertTrue($success);

        $user = $collection->get($id);
        self::assertSame('Bob', $user['name']);
    }

    public function testDelete() : void
    {
        $id = new ObjectId();
        $collection = new MongoCollection($this->getConnection(), 'users');
        $success = $collection->insert([
            '_id' => $id,
            'name' => 'John',
            'lastName' => 'Doe'
        ]);
        self::assertTrue($success);

        $success = $collection->delete($id);
        self::assertTrue($success);

        $user = $collection->get($id);
        self::assertNull($user);
    }

    public function testFindAndDelete() : void
    {
        $this->generateUsers(10);
        $collection = new MongoCollection($this->getConnection(), 'users');
        $modified = $collection->findAndDelete([]);

        self::assertSame(10, $modified);
    }
}
