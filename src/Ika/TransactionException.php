<?php

namespace Ika;

class TransactionException extends \Exception
{
    protected $command;

    protected $direction;

    /**
     * Sets the command bound to the exception
     *
     * @param Command $command
     *
     * @return  void
     */
    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Returns the command bound to the exception
     *
     * @return Command
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Sets the current direction when the exception is thrown
     *
     * @param string $direction
     *
     * @return  void
     */
    public function setDirection($direction)
    {
        $this->direction = $direction;
    }

    /**
     * Returns the current direction when the exception is thrown
     *
     * @return string
     */
    public function getDirection()
    {
        return $this->direction;
    }

    /**
     * Shortcut to know the direction when the exception is thrown
     *
     * @return boolean
     */
    public function isUp()
    {
        return Transaction::DIRECTION_UP == $this->direction;
    }

    /**
     * Shortcut to know the direction when the exception is thrown
     *
     * @return boolean
     */
    public function isDown()
    {
        return Transaction::DIRECTION_DOWN == $this->direction;
    }
}
