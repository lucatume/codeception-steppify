<?php
namespace tad\Tests\Modules;

use Codeception\Module;

class ModuleTwo extends Module
{
    public function seeSomething()
    {

    }

    /**
     * @param $name
     *
     * @gherkin then
     */
    public function seeElement($name)
    {

    }

    /**
     * @param string $name
     * @param string $color
     *
     * @gherkin then
     */
    public function seeElementWithColor($name, $color)
    {

    }

    /**
     * @param string $context
     * @param string $text
     *
     * @gherkin then
     */
    public function seeElementInContext($context, $text = null)
    {

    }

    /**
     * @gherkin given
     */
    public function haveElementWithColorAndSize($element, $color, $size)
    {

    }

}