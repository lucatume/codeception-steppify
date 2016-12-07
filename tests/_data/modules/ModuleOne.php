<?php
namespace tad\Tests\Modules;

use Codeception\Module;

class ModuleOne extends Module
{
    public function doSomething()
    {

    }

    /**
     * @gherkin given
     */
    public function doSomethingTwo()
    {

    }

    /**
     * @gherkin when
     */
    public function doSomethingThree()
    {

    }

    /**
     * @gherkin then
     */
    public function doSomethingFour()
    {

    }

    /**
     * @gherkin given
     *
     * @param $arg1
     */
    public function doSomethingWithStringOne($arg1)
    {
        \tad\Tests\Modules\Support\_recordCall(__CLASS__, 'doSomethingWithStringOne', func_get_args());
    }

    /**
     * @gherkin given
     *
     * @param string $arg1
     */
    public function doSomethingWithStringTwo($arg1 = 'foo')
    {
        \tad\Tests\Modules\Support\_recordCall(__CLASS__, 'doSomethingWithStringOne', func_get_args());
    }

    /**
     * @gherkin no
     */
    public function noGherkin()
    {
    }

    public function doSomethingWithArray(array $args)
    {

    }
}