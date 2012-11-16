Ika
===

PHP 5.3 library to use command/transaction design pattern

Concept
-------
Ika relies on doing the same as SQL transations but with eclectic actions, and if an error occured roll back these actions.

An example of application will be an installer, that perform multiple actions like create folder, duplicate files, run SQL queries etc and if one of these actions doesn't work, must be cleaned previous actions.

How it works
------------

You create several commands, each one has a up action and a down action.
When you will start the transaction, all up action of your commands will be run,
in the order they were added.

If an error occurred, you can roll back. It will run all down action of all succeeded commands
in reverse. That's it. Simple.

Installation
------------

### Old school

Download the latest version of Ika and add the `Ika` namespace
to your PSR-0 autoloading system, or simply require the `src/autoload.php`

### Composer

Just create a composer.json file and run the php composer.phar install command to install it:

```json
{
    "require": {
        "devster/ika": ">=1.0.*"
    }
}
```

Usage
-----

### Basic

Here a small example of basic usage with thin commands:

```php
use Ika\Transaction;

$t = new Transaction;

// Register new command to the transaction and set it up
$t->register('new_dir')->setUp(function($prev, $command) {
    // the `command` var is the new command object you just registered
    // $prev is the return of the previous hook/command
    // Here it is NULL because there is nothing before
    mkdir('test');
})->setDown(function($prev, $command) {
    rmdir('test');
});

// add a second command, this library is useless for just one command :)
$t->register('create_file')->setUp(function() {
    // some code that throw an exception
    throw new Exception('Error !');
})->setDown(function(){});

// Here we go! We run the transaction
try {
    $result = $t->begin();
} catch(TransactionException $e) {
    // Execute just the down action of the first command `rmdir('test')`
    $t->rollback();
}
```

### With Bigger commands

If your commands are more complex you can extend the `Ika\Command`:

```php
use Ika\Command;

class MyCommand extends Command
{
    public function getName()
    {
        return 'my_command';
    }

    public function isEnabled()
    {
        // your code that decide if this command
        // must be run when the transaction will be executed
        // By default return true

        return true;
    }

    public function up($prev)
    {
        mkdir('test');
    }

    public function down($prev)
    {
        rmdir(test);
    }
}
```
```php
$t = new Transaction;

// add your new fresh command
$t->add(new MyCommand);
// add a bunch of commands
//$t->addCommands(array(new Command, ...));


try {
    $t->begin();
} catch(TransactionException $e) {
    $t->rollback();
}
```

### Hooks

There is a hooks system integrated in Ika. here's the list:

```
- pre      // execute before any actions up or down
- post     // executed after any actions up or down
- preUp    // executed before up actions
- postUp   // executed after up actions
- preDown  // executed before down actions
- postDown // executed after down actions
```
```php
$t = new Transaction;

$t->addHook('preUp', function($prev, $command) {
    // $prev is the return of the previous hook/command
    // $command is the current command executed
    echo 'Execution of '.$command->getName();
});
```

If your hooks are more complex you can extends `Ika\Transaction`:

```php
use Ika\Transaction;

class MyTransaction extends Transaction
{
    public function initialize()
    {
        $this->addHook('pre', function($prev, $command, $direction){
            echo 'Execution of '.$direction.' '.$command->getName();
        });

        $this->addHook('postDown', function(){});

        // etc
    }
}
```

### Tricks

You can start the transaction at the command you want:

```php
$t->begin('my_command');
```

You start the rollback at the command you want:
```php
$t->rollback('my_command');
```


You can run the transaction

About
-----

### Requirements

- Any flavor of PHP 5.3 should do

Author
------

- Jeremy Perret <jeremy@devster.org>

### License


Ika is licensed under the MIT License - see the LICENSE file for details