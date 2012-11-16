<?php

namespace Ika;

class Transaction
{
    CONST HOOK_PRE = 'pre';
    CONST HOOK_POST = 'post';
    CONST HOOK_PRE_UP = 'preUp';
    CONST HOOK_PRE_DOWN = 'preDown';
    CONST HOOK_POST_UP = 'postUp';
    CONST HOOK_POST_DOWN = 'postDown';

    CONST DIRECTION_UP = 'up';
    CONST DIRECTION_DOWN = 'down';

    protected $commands = array();

    protected $hooks = array();

    protected $offset_error;

    protected $offset_start;

    protected $initialized = false;

    public function __construct()
    {
        $this->hooks = array(
            self::HOOK_PRE => array(),
            self::HOOK_POST => array(),
            self::HOOK_PRE_UP => array(),
            self::HOOK_PRE_DOWN => array(),
            self::HOOK_POST_UP => array(),
            self::HOOK_POST_DOWN => array(),
        );
    }

    /**
     * Extend this method to initialize your Transaction,
     * set your hooks etc
     *
     * @return void
     */
    public function initialize()
    {
        return null;
    }

    /**
     * Runs this method one time for both begin and rollback methods
     *
     * @return void
     */
    protected function _init()
    {
        if (! $this->initialized) {
            $this->initialize();
            $this->initialized = true;
        }
    }

    /**
     * Add a hook to the stack
     *
     * @param string $place The name of a hook
     *
     * @param \Closure $hook  The \Closure to execute when the hook is triggered
     *
     * @return Transaction The current instance
     */
    public function addHook($place, \Closure $hook)
    {
        if (! in_array($place, array_keys($this->hooks))) {
            throw new \LogicException(sprintf("The hook `%s` doesn't exist", $place));
        }

        $this->hooks[$place][] = $hook;

        return $this;
    }

    /**
     * Returns a new command object
     * already registered to the commands stack
     *
     * @param  string $name The command name
     *
     * @return Command The new command object
     */
    public function register($name = null)
    {
        return $this->add(new Command($name));
    }

    /**
     * Adds command to te stack
     *
     * Adds command with name (if possible)
     * to allow to override the command with the same name
     *
     * @param Command $command A Command object
     *
     * @return Command The registered command
     */
    public function add(Command $command)
    {
        $command->setTransaction($this);

        if ($command->getName()) {
            $this->commands[$command->getName()] = $command;
        } else {
            $this->commands[] = $command;
        }

        return $command;
    }

    /**
     * Adds an array of command objects.
     *
     * @param Command[] $commands An array of commands
     *
     * @return  void
     */
    public function addCommands(array $commands)
    {
        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    /**
     * Returns the commands registered in the transaction
     *
     * @return array The commands
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Execute the right stacks of hooks
     *
     * @param  string  $type            `pre` or `post`
     *
     * @param  string  $direction       `up` or `down`
     *
     * @param  mixed   $previous_return
     *
     * @param  Command $command         The current command in the loop
     *
     * @return mixed
     */
    protected function executeHooks($type, $direction, $previous_return, Command $command)
    {
        if (self::HOOK_PRE == $type && self::DIRECTION_UP == $direction) {
            $hooks = array_merge($this->hooks[self::HOOK_PRE], $this->hooks[self::HOOK_PRE_UP]);
        } elseif (self::HOOK_PRE == $type && self::DIRECTION_DOWN == $direction) {
            $hooks = array_merge($this->hooks[self::HOOK_PRE], $this->hooks[self::HOOK_PRE_DOWN]);
        } elseif (self::HOOK_POST == $type && self::DIRECTION_UP == $direction) {
            $hooks = array_merge($this->hooks[self::HOOK_POST], $this->hooks[self::HOOK_POST_UP]);
        } elseif (self::HOOK_POST == $type && self::DIRECTION_DOWN == $direction) {
            $hooks = array_merge($this->hooks[self::HOOK_POST], $this->hooks[self::HOOK_POST_DOWN]);
        }

        foreach($hooks as $hook) {
            $previous_return = call_user_func($hook, $previous_return, $command, $direction);
        }

        return $previous_return;
    }

    /**
     * Starts the Transaction.
     *
     * It will execute the `up` action of all commands
     * registered by the transaction. When an exception is thrown
     * the transaction catch it and store the index of the responsible command
     * before re-throw the same exception decorated with usefull infos
     *
     * @param  mixed $offset Allows to set the starting command
     *
     * @return mixed Returns the result of the last hook/command
     */
    public function begin($offset = null)
    {
        $this->_init();

        $previous_return = null;
        $this->offset_error = null;
        $this->offset_start = $offset;

        $start_begin = $offset ? false : true;

        foreach($this->commands as $index => $command) {
            if (! $start_begin && $this->offset_start == $index) {
                $start_begin = true;
            }

            try {
                if (! $command->isEnabled() || ! $start_begin) {
                    continue;
                }

                // pre hook
                $previous_return = $this->executeHooks(self::HOOK_PRE, self::DIRECTION_UP, $previous_return, $command);

                $previous_return = $command->runUp($previous_return);

                // post hook
                $previous_return = $this->executeHooks(self::HOOK_POST, self::DIRECTION_UP, $previous_return, $command);
            } catch(\Exception $e) {
                $this->offset_error = $index;

                $ie = new TransactionException($e->getMessage(), $e->getCode());
                $ie->setCommand($command);
                $ie->setDirection(self::DIRECTION_UP);
                throw $ie;
            }
        }

        return $previous_return;
    }

    /**
     * Roll back the Transaction
     *
     * It will execute the `down`action of the succeeded commands
     * or all if no exception has been thrown during the begin method.
     *
     * @param  mixed $offset Allows to set the starting command
     *
     * @return mixed Returns the result of the last hook/command
     */
    public function rollback($offset = null)
    {
        $this->_init();

        $previous_return = null;

        $commands = array_reverse($this->commands, true);

        $offset = $offset ?: $this->offset_error;

        $start_rollback = is_null($offset) ? true : false;

        foreach($commands as $index => $command) {
            if (! $start_rollback && $offset == $index) {
                $start_rollback = true;
                continue;
            }

            try {
                if (! $command->isEnabled() || ! $start_rollback) {
                    continue;
                }

                // pre hook
                $previous_return = $this->executeHooks(self::HOOK_PRE, self::DIRECTION_DOWN, $previous_return, $command);

                $previous_return = $command->runDown($previous_return);

                // post hook
                $previous_return = $this->executeHooks(self::HOOK_POST, self::DIRECTION_DOWN, $previous_return, $command);
            } catch(\Exception $e) {
                $ie = new TransactionException($e->getMessage(), $e->getCode());
                $ie->setCommand($command);
                $ie->setDirection(self::DIRECTION_DOWN);
                throw $ie;
            }

            if ($this->offset_start == $index) {
                break;
            }
        }

        return $previous_return;
    }
}
