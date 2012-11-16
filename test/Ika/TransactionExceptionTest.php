<?php

namespace Ika\Test;

use Ika\Command;
use Ika\Transaction;
use Ika\TransactionException;

class TransactionExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testSetCommandGetCommand()
    {
        $e = new TransactionException;
        $c = new Command('my_command');

        $this->assertNull($e->getCommand());

        $e->setCommand($c);

        $this->assertEquals($e->getCommand(), $c);
    }

    public function testSetDirectionGetDirection()
    {
        $e = new TransactionException;

        $this->assertNull($e->getDirection());

        $e->setDirection(Transaction::DIRECTION_UP);

        $this->assertEquals(Transaction::DIRECTION_UP, $e->getDirection());
    }

    public function testIsUpIsDown()
    {
        $e = new TransactionException;
        $e->setDirection(Transaction::DIRECTION_UP);

        $this->assertTrue($e->isUp());

        $e->setDirection(Transaction::DIRECTION_DOWN);

        $this->assertFalse($e->isUp());

        $this->assertTrue($e->isDown());
    }
}
