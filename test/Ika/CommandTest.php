<?php

namespace Ika\Test;

use Ika\Command;
use Ika\Transaction;

class CommandTest extends \PHPUnit_Framework_TestCase
{
    public function testGetTransactionSetTransaction()
    {
        $c = new Command;
        $t = new Transaction;

        $this->assertNull($c->getTransaction());

        $c->setTransaction($t);

        $this->assertInstanceOf('Ika\\Transaction', $c->getTransaction());
        $this->assertEquals($t, $c->getTransaction());
    }

    public function testGetNameSetName()
    {
        $name = 'mycommand';
        $name2 = 'othername';

        $command = new Command($name);

        $this->assertEquals($name, $command->getName());

        $command->setName($name2);

        $this->assertEquals($name2, $command->getName());
    }
}
