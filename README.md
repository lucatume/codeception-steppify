Steppify

*Generate Gherkin steps from Codeception modules.*

[![Build Status](https://travis-ci.org/lucatume/codeception-steppify.svg?branch=master)](https://travis-ci.org/lucatume/codeception-steppify)

## Installation
Install this package using [Composer](https://getcomposer.org/):

```shell
composer require --dev lucatume/codeception-steppify
```

and then add the command to the custom commands used by your project following [Codeception documentation](http://codeception.com/docs/08-Customization#Custom-Commands) on the subject:

```yaml
extensions:
    commands: [tad\Codeception\Command\Steppify]
```

## Usage
The command will generate traits usable in [Codeception](http://codeception.com/ "Codeception - BDD-style PHP testing.") tester classes from codeception modules.  
As an example I might want to generate [Gherkin](!g) steps to use Codeception own [PhpBrowser](!g codeception PhpBrowser module) module methods in Gherkin features:

```
codecept gherkin:steppify PhpBrowser
```

The command will generate `PhpBrowserGherkinSteps.php`, a PHP `trait` file, in the tests `_support/_generated` folder.
To start using the new methods all that's required is to add a `use` statement for the `PhpBrowserSteps` trait in the suite `Tester` class:

```php
<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\FunctionalTesterActions;
    use _generated\PhpBrowserGherkinSteps;

   /**
    * Define custom actions here
    */
}

```

You will now be able to write Gherkin features using steps generated from `PhpBrowser` module provided methods like:

```gherkin
Scenario: methods provided by Codeception PhpBrowser module are available as Gherkin steps

    Feature: I can go on the site homepage

    When I am on page '/'
    Then I can see element 'body.home'
```

The command is not limited to Codeception default modules only and will work with any custom module provided by other libraries or custom made for the project.
While the command will make an extra effort to support modules in the `Codeception\Module\` name space modules defined outside of that namespace will require the specification of the fully qualified name to work:

```shell
codecept gherkin:steppify "Acme\Project\Tests\Modules\ModuleOne"
```

This means that ```gherkin:steppify ModuleName``` is a shortcut for ```gherkin:steppify "Codeception\Module\ModuleName"``` and you may provide any other compatible namespaced class.

## Controlling the output methods

While the command will try to be "smart" and helpful in generating the methods signatures it has, and will always have, limits.
For this reason the method signature generation logic will take into account cascading definitions during the generation process:

* if available use the configuration file
* else available use the method documentation block
* else fallback on using the built-in logic

### Docblock tags
The command supports two docblock tags to control the generation:

* `@gherkin` - can be `no` to avoid the method from generating any step, or a comma separated list of step types (`given`, `when`, `then`).

Please note that if [Gherkin step compatible step definitions](http://codeception.com/docs/07-BDD#Step-Definitions) are found in the method doc block than those will be used.

### Configuration file
The command supports a `--steps-config <file.yml>` option that allows specifying which methods and how steps should be generated.
The file has the following format:

```yaml
namespace: Acme\Project
modules:
  PhpBrowser:
    methods:
      amOnPage:
        generates: [given, when]
        step: I visit page :page
  Acme\Modules\SomeModule:
    exclude:
      - methodTwo
    methods:
      - haveAuthKeyInDatabase:
        generates: [given]
        step: I have authorization key :key in database
  <module>:
    exclude:
      - <excluded method one>
      - <excluded method two>
    methods:
      - <method name>:
        generates: [given, when, then]
        step: <step definition template>
```

If a `namespace` option is specified the one specified in the settings file will be overridden by it.

## Options
The command supports options meant to make its output controllable to a comfortable degree:

* `--steps-config <file>` - see [the configuration file section](#configuration-file); allows specifying the path to a step definition generation configuration file.
* `--prefix <prefix>` - allows specifying a string that should be appended to the generated step file name.
* `--namespace <namespace>` -- allows specifying the namespace steps will be generated into; by default steps would be generated in the `_generated` namespace, passing `Acme\Namespace` to this option will make traits be generated into the `Acme\Project\_generated` namespace.

