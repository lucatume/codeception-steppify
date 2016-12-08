<?php

namespace tad\Tests\Modules;


use Codeception\Module;

class ModuleSix extends Module
{
    /**
     * @Given There is one user in the database
     */
    public function haveSomeUser()
    {
    }

    /**
     * @Given /I have a regex "([^"]*)" set up/
     */
    public function haveSomeRegex()
    {

    }
}