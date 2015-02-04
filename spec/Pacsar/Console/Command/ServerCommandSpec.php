<?php

namespace spec\Pacsar\Console\Command;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ServerCommandSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pacsar\Console\Command\ServerCommand');
    }

    function it_is_a_Symfony_Console_Command()
    {
        $this->shouldHaveType('Symfony\Component\Console\Command\Command');
    }
}
