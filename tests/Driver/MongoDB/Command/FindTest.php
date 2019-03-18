<?php declare(strict_types=1);

namespace FatCode\Tests\Storage\Driver\MongoDB\Command;

use FatCode\Storage\Driver\MongoDB\Command\Find;
use FatCode\Storage\Driver\MongoDB\Command\Operation\Join;
use FatCode\Storage\Driver\MongoDB\Command\Operation\Limit;
use FatCode\Storage\Driver\MongoDB\Command\Operation\Select;
use PHPUnit\Framework\TestCase;

final class FindTest extends TestCase
{
    use CommandTest;

    public function testFindWithoutOptions(): void
    {
        $this->generateUsers(100);
        $connection = $this->getConnection();
        $cursor = $connection->execute(new Find($this->getUsersCollection()));
        $items = $cursor->toArray();
        self::assertCount(100, $items);
    }

    public function testFindWithFilter(): void
    {
        $users = $this->generateUsers(100);
        $eyeColor = current($users)['eye_color'];
        $connection = $this->getConnection();
        $cursor = $connection->execute(new Find($this->getUsersCollection(), ['eye_color' => $eyeColor]));

        $found = $cursor->toArray();

        self::assertNotEmpty($found);
        foreach ($found as $user) {
            self::assertSame($eyeColor, $user['eye_color']);
        }
    }

    public function testFindWithProjection(): void
    {
        $this->generateUsers(10);
        $connection = $this->getConnection();
        $cursor = $connection->execute(
            new Find(
                $this->getUsersCollection(),
                [],
                new Select('eye_color', 'number')
            )
        );

        $found = $cursor->toArray();
        self::assertCount(10, $found);

        foreach ($found as $user) {
            self::assertArrayNotHasKey('balance', $user);
            self::assertArrayNotHasKey('language', $user);
            self::assertArrayNotHasKey('name', $user);
            self::assertArrayHasKey('eye_color', $user);
            self::assertArrayHasKey('number', $user);
            self::assertArrayHasKey('_id', $user);
        }
    }

    public function testFindWithJoin(): void
    {
        $this->generateUsersAndFavourites(20, 3);
        $connection = $this->getConnection();

        $cursor = $connection->execute(
            Find::all(
                $this->getUsersCollection(),
                new Join($this->getFavouritesCollection(), '_id', 'user_id', 'favourites'),
                new Select('name', 'favourites.color', 'favourites.fruit'),
                new Limit(4)
            )
        );
        $items = $cursor->toArray();

        self::assertCount(4, $items);
        foreach ($items as $item) {
            self::assertArrayHasKey('favourites', $item);
            self::assertArrayHasKey('name', $item);
            self::assertCount(3, $item['favourites']);
            self::assertArrayNotHasKey('eye_color', $item);
        }
    }
}
