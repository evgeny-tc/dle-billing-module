<?php

use Billing\Api\Balance;
use Billing\BalanceException;
use PHPUnit\Framework\TestCase;

final class BalanceTest extends TestCase
{
    private FakeDb $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new FakeDb();
        $this->db->addUser(1, 'alice', 100);
        $this->db->addUser(2, 'bob', 10);

        $GLOBALS['db'] = $this->db;
        $GLOBALS['member_id'] = $this->db->users[1];
        $GLOBALS['_TIME'] = 1_700_000_000;
        $GLOBALS['config'] = ['http_home_url' => '/'];

        Balance::reset();
        Balance::Init([
            'fname' => 'user_balance',
            'format' => 'float',
            'currency' => 'рубль,рубля,рублей',
            'commission' => '',
            'admin' => '',
        ]);
    }

    protected function tearDown(): void
    {
        Balance::reset();
        parent::tearDown();
    }

    public function testTransferMovesMoneyBetweenUsers(): void
    {
        Balance::Init()
            ->From(userLogin: 'alice', sum: 25)
            ->To(userLogin: 'bob', sum: 25);

        $this->assertEquals(75.0, (float) $this->db->users[1]['user_balance']);
        $this->assertEquals(35.0, (float) $this->db->users[2]['user_balance']);
    }

    public function testFromRejectsInsufficientBalance(): void
    {
        $this->expectException(BalanceException::class);
        $this->expectExceptionMessage('balance.check');

        Balance::Init()->From(userLogin: 'alice', sum: 1000);
    }

    public function testNestedTransactionStartsOnce(): void
    {
        $api = Balance::Init();

        $api->Transaction();
        $api->Transaction();
        $api->Commit();
        $api->Commit();

        $starts = array_values(array_filter(
            $this->db->queries,
            static fn(string $sql) => preg_match('/^START TRANSACTION/i', trim($sql))
        ));
        $commits = array_values(array_filter(
            $this->db->queries,
            static fn(string $sql) => preg_match('/^COMMIT/i', trim($sql))
        ));

        $this->assertCount(1, $starts);
        $this->assertCount(1, $commits);
    }

    public function testCommentDoesNotOpenPmTransactionBeforeCommit(): void
    {
        $api = Balance::Init()->Transaction();

        $api->Comment(
            userLogin: 'alice',
            minus: 10,
            comment: 'test',
            pm: true
        )->From(userLogin: 'alice', sum: 10);

        $beforeCommit = $this->db->queries;
        $this->assertNotEmpty(array_filter(
            $beforeCommit,
            static fn(string $sql) => preg_match('/^START TRANSACTION/i', trim($sql))
        ));
        $this->assertEmpty(array_filter(
            $beforeCommit,
            static fn(string $sql) => preg_match('/^COMMIT/i', trim($sql))
        ));

        $api->Commit();

        $this->assertEquals(90.0, (float) $this->db->users[1]['user_balance']);
        $this->assertNotEmpty($this->db->history);
    }

    public function testRollbackRestoresBalance(): void
    {
        $api = Balance::Init()->Transaction();
        $api->From(userLogin: 'alice', sum: 40);
        $api->Rollback();

        $this->assertEquals(100.0, (float) $this->db->users[1]['user_balance']);
    }
}
