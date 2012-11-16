<?php

namespace Ika\Test;

require_once __DIR__.'/../res/CommandExtended.php';

use Ika\Command;
use Ika\Transaction;
use Ika\TransactionException;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    public function commandProvider()
    {
        // register method
        $t1 = new Transaction;
        $t1->register('my_command');
        $t1->register();

        // add method
        $t2 = new Transaction;
        $t2->add(new Command('my_command'));
        $t2->add(new Command());

        // addCommands method
        $t3 = new Transaction;
        $t3->addCommands(array(new Command('my_command'), new Command()));

        return array(
            array($t1),
            array($t2),
            array($t3),
        );
    }

    /**
     * @dataProvider commandProvider
     */
    public function testRegisterAndAdd(Transaction $t)
    {
        $commands = $t->getCommands();

        $this->assertTrue(isset($commands['my_command']));
        $this->assertInstanceOf('Ika\\Command', $commands['my_command']);
        $this->assertInstanceOf('Ika\\Transaction',$commands['my_command']->getTransaction());

        $this->assertTrue(isset($commands[0]));
        $this->assertInstanceOf('Ika\\Command', $commands[0]);
        $this->assertInstanceOf('Ika\\Transaction',$commands[0]->getTransaction());
    }

    protected function transactionProvider()
    {
        $t = new Transaction;
        $t->register('1')->setUp(function($prev) {
            return $prev + 1;
        })->setDown(function($prev) {
            return $prev + 1;
        });

        $t->register('2')->setUp(function($prev) {
            return $prev + 2;
        })->setDown(function($prev) {
            return $prev + 2;
        });

        $t->register('3')->setUp(function($prev) {
            return $prev + 3;
        })->setDown(function($prev) {
            return $prev + 3;
        });

        $t->add(new CommandExtended(true));

        return $t;
    }

    public function testCommandDisabled()
    {
        $t = $this->transactionProvider();
        $t->add(new CommandExtended(false));

        $this->assertEquals(6, $t->begin());

        $this->assertEquals(6, $t->rollback());
    }

    public function testBegin()
    {
        $t = $this->transactionProvider();

        $this->assertEquals(10, $t->begin());
    }

    public function testRollback()
    {
        $t = $this->transactionProvider();
        $t->begin();
        $this->assertEquals(10, $t->rollback());
    }

    public function testTransactionWithException()
    {
        $t = $this->transactionProvider();

        $t->register('3')->setUp(function($prev) {
            throw new \Exception('error!');
        });

        try {
            $t->begin();
        } catch(TransactionException $e) {
            $offset_error = $e->getCommand()->getName();
            $return = $t->rollback();
        }

        $this->assertEquals('3', $offset_error);
        $this->assertInstanceOf('Ika\\Transaction', $e->getCommand()->getTransaction());
        $this->assertEquals(3, $return);
    }

    public function testBeginWithManualOffset()
    {
        $t = $this->transactionProvider();

        $this->assertEquals(9, $t->begin('2'));
        $this->assertEquals(9, $t->rollback());
    }

    public function testRollbackWithManualOffset()
    {
        $t = $this->transactionProvider();

        $this->assertEquals(1, $t->rollback('2'));
    }

    public function testHookUp()
    {
        $t = $this->transactionProvider();

        $t->addHook('pre', function($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('preUp', function ($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('post', function($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('postUp', function ($prev, Command $command) {
            return $prev + 1;
        });

        $this->assertEquals(26 , $t->begin());
    }

    public function testHookDown()
    {
        $t = $this->transactionProvider();

        $t->addHook('pre', function($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('preDown', function ($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('post', function($prev, Command $command) {
            return $prev + 1;
        });
        $t->addHook('postDown', function ($prev, Command $command) {
            return $prev + 1;
        });

        $this->assertEquals(26 , $t->rollback());
    }
}
