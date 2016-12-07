Steppify

*Generate Gherkin steps from Codeception modules.*

## Installation
Install this package using [Composer](https://getcomposer.org/):

```shell
composer require --dev lucatume/codeception-steppify
```

and then add the command to the custom commands used by your project following [Codeception documentation](http://codeception.com/docs/08-Customization#Custom-Commands) on the subject:

```yaml
extensions:
    commands: [tad\Command\Steppify]
```

## Usage
The command will generate traits usable in [Codeception](http://codeception.com/ "Codeception - BDD-style PHP testing.") tester classes from codeception modules.  
As an example I might want to generate [Gherkin](!g) steps to use Codeception own [PHPBrowser](!g codeception phpbrowser module) methods in Gherkin features:

```
codecept gherkin:steppify PHPBrowser
```

The command will generate a `PHPBrowserSteps.php`, a php `trait`, file in the tests `_support/_generated` folder.  
To start using the new methods all that's required is to add a `use` statement for the `PHPBrowserSteps` trait in the suite `Tester` class:

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
    use _generated\PHPBrowserSteps;

   /**
    * Define custom actions here
    */
}

```

You will now be able to write Gherkin features using steps generated from `PHPBrowser` module provided methods like:

```gherkin
Scenario: methods provided by Codeception PHPBrowser module are available as Gherkin steps

    Feature: I can go on the site homepage

    When I am on page '/'
    Then I can see element 'body.home'
```