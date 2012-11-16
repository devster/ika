<?php

namespace Ika\Test;

use Ika\Command;

class CommandExtended extends Command
{
    public function __construct($enabled)
    {
        $this->enabled = $enabled;
        parent::__construct('my_command');
    }

    public function isEnabled()
    {
        return $this->enabled;
    }

    public function up($prev)
    {
        return $prev + 4;
    }

    public function down($prev)
    {
        return $prev +4;
    }
}