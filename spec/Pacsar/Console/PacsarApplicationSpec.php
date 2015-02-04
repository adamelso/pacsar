<?php

namespace spec\Pacsar\Console;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class PacsarApplicationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Pacsar\Console\PacsarApplication');
    }

    function it_is_a_Symfony_Console_Application()
    {
        $this->shouldHaveType('Symfony\Component\Console\Application');
    }

    function it_is_container_aware()
    {
        $this->shouldImplement('Symfony\Component\DependencyInjection\ContainerAwareInterface');
    }
}
