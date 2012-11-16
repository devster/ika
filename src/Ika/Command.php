<?php

namespace Ika;

class Command
{
    protected $up_code;

    protected $down_code;

    protected $name;

    protected $transaction;

    public function __construct($name = null)
    {
        $this->name = $name;
    }

     /**
     * Sets the Transaction instance for this command.
     *
     * @param Transaction $transaction A Transaction instance
     *
     * @return  void
     */
    public function setTransaction(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Returns the Transaction.
     *
     * @return Transaction The Transaction instance
     */
    public function getTransaction()
    {
        return $this->transaction;
    }

    /**
     * Sets the name of the command.
     *
     * It will be use if your code throw exceptions
     *
     * @param string $name The command name
     *
     * @return Command The current instance
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the command name.
     *
     * Can be extend to return directly the command name
     *
     * @return string The command name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Checks whether the command is enabled or not in the current environment
     *
     * Override this to check for x or y and return false if the command can not
     * run properly under the current conditions.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return true;
    }

    /**
     * If this method is used, it overrides the code defined in the up() method.
     *
     * @param \Closure $code A \Closure
     *
     * @return Command The current instance
     */
    public function setUp(\Closure $code)
    {
        $this->up_code = $code;

        return $this;
    }

    /**
     * If this method is used, it overrides the code defined in the down() method.
     *
     * @param \Closure $code A \Closure
     *
     * @return Command The current instance
     */
    public function setDown(\Closure $code)
    {
        $this->down_code = $code;

        return $this;
    }

    /**
     * Executes the up code of the command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * up() method, you set the code to execute by passing
     * a Closure to the setUp() method.
     *
     * @param  mixed $previous_return The returns of the previous command
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see    setUp()
     */
    protected function up($previous_return)
    {
        throw new \LogicException('You must override the up() method in the concrete command class.');
    }

    /**
     * Executes the down code of the command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * down() method, you set the code to execute by passing
     * a Closure to the setDown() method.
     *
     * @param  mixed $previous_return The returns of the previous command
     *
     * @throws \LogicException When this abstract method is not implemented
     *
     * @see    setDown()
     */
    protected function down($previous_return)
    {
        throw new \LogicException('You must override the down() method in the concrete command class.');
    }

    /**
     * Runs up the command.
     *
     * The code to execute is either defined directly with the
     * setUp() method or by overriding the up() method
     * in a sub-class.
     *
     * @param  mixed $previous_return The returns of the previous command
     *
     * @return void
     */
    public function runUp($previous_return = null)
    {
        if ($this->up_code) {
            return call_user_func($this->up_code, $previous_return, $this);
        }

        return $this->up($previous_return);
    }

    /**
     * Runs down the command.
     *
     * The code to execute is either defined directly with the
     * setDown() method or by overriding the down() method
     * in a sub-class.
     *
     * @param  mixed $previous_return The returns of the previous command
     *
     * @return void
     */
    public function runDown($previous_return= null)
    {
        if ($this->down_code) {
            return call_user_func($this->down_code, $previous_return, $this);
        }

        return $this->down($previous_return);
    }
}
