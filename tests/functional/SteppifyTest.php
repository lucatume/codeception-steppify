<?php


use Behat\Gherkin\Node\TableNode;
use Codeception\Configuration;
use Codeception\Scenario;
use Codeception\Step\Action;
use Codeception\Util\Template;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use tad\Codeception\Command\Steppify;

class SteppifyTest extends \Codeception\Test\Unit
{
    /**
     * @var string
     */
    protected $moduleStepsFile;

    /**
     * @var string
     */
    protected $targetSuiteConfigFile;

    /**
     * @var string
     */
    protected $suiteConfigBackup;
    /**
     * @var \FunctionalTester
     */
    protected $tester;

    /**
     * @var array
     */
    protected $targetSuiteConfig = [];

    /**
     * @var FilesystemIterator
     */
    protected $testModules;

    /**
     * @var string
     */
    protected $classTemplate = <<< EOF
class {{name}} {

    use {{trait}};

    protected \$scenario;
    
    public function __construct(\$scenario) {
        \$this->scenario = \$scenario;
    }

    protected function getScenario() {
        return \$this->scenario;
    }
}
EOF;


    /**
     * @test
     * it should exist as a command
     */
    public function it_should_exist_as_a_command()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleZero'
        ]);
    }

    /**
     * @param Application $app
     */
    protected function addCommand(Application $app)
    {
        $app->add(new Steppify('steppify'));
    }

    /**
     * @test
     * it should return error message if target module does not exist
     */
    public function it_should_return_error_message_if_target_module_does_not_exist()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'NotModule'
        ]);

        $this->assertContains("NotModule' does not exist", $commandTester->getDisplay());
    }

    /**
     * @test
     * it should generate an helper steps module
     */
    public function it_should_generate_an_helper_steps_module()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleZero',
        ]);


        $this->assertFileExists($this->getStepsFileForModule('ModuleZero'));
    }

    /**
     * @test
     * it should generate a trait file
     */
    public function it_should_generate_a_trait_file()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleZero',
        ]);

        $this->assertFalse(trait_exists('_generated\ModuleZeroGherkinSteps', false));
        $stepsFile = $this->getStepsFileForModule('ModuleZero');
        $this->assertFileExists($stepsFile);

        require_once $stepsFile;

        $this->assertTrue(trait_exists('_generated\ModuleZeroGherkinSteps', false));
    }

    /**
     * @test
     * it should not generate any method if the suite Tester class contains no methods
     * @depends it_should_generate_a_trait_file
     */
    public function it_should_not_generate_any_method_if_the_suite_tester_class_contains_no_methods()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = md5(uniqid('foo',true));

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleZero',
            '--postfix' => $id
        ]);

        $class = 'ModuleZeroGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $ref = new ReflectionClass('_generated\ModuleZeroGherkinSteps' . $id);
        $methods = $ref->getMethods();

        $this->assertEmpty(array_filter($methods, function (ReflectionMethod $method) {
            // exclude utility methods
            return !preg_match('/^_/', $method->name);
        }));
    }

    /**
     * @test
     * it should generate given, when and then step for method by default
     */
    public function it_should_generate_given_when_and_then_step_for_method_by_default()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomething'));

        $reflectedMethod = $ref->getMethod('step_doSomething');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Given I do something', $methodDockBlock);
        $this->assertContains('@When I do something', $methodDockBlock);
        $this->assertContains('@Then I do something', $methodDockBlock);
    }

    /**
     * @test
     * it should generate given step only if specified
     */
    public function it_should_generate_given_step_only_if_specified()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingTwo'));

        $reflectedMethod = $ref->getMethod('step_doSomethingTwo');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Given I do something two', $methodDockBlock);
        $this->assertNotContains('@When I do something two', $methodDockBlock);
        $this->assertNotContains('@Then I do something two', $methodDockBlock);
    }

    /**
     * @test
     * it should generate when step only if specified
     */
    public function it_should_generate_when_step_only_if_specified()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingThree'));

        $reflectedMethod = $ref->getMethod('step_doSomethingThree');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertNotContains('@Given I do something three', $methodDockBlock);
        $this->assertContains('@When I do something three', $methodDockBlock);
        $this->assertNotContains('@Then I do something three', $methodDockBlock);
    }

    /**
     * @test
     * it should generate then step only if specified
     */
    public function it_should_generate_then_step_only_if_specified()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingFour'));

        $reflectedMethod = $ref->getMethod('step_doSomethingFour');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertNotContains('@Given I do something four', $methodDockBlock);
        $this->assertNotContains('@When I do something four', $methodDockBlock);
        $this->assertContains('@Then I do something four', $methodDockBlock);
    }

    /**
     * @test
     * it should pass string arguments directly to original method
     */
    public function it_should_pass_string_arguments_directly_to_original_method()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $trait = '_generated\\' . $class;
        $this->assertTrue(trait_exists($trait));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingWithStringOne'));

        $reflectedMethod = $ref->getMethod('step_doSomethingWithStringOne');

        $parameters = $reflectedMethod->getParameters();

        $this->assertNotEmpty($parameters);
        $this->assertTrue($parameters[0]->name === 'arg1');
    }

    /**
     * @test
     * it should allow defaulting string arguments
     */
    public function it_should_allow_defaulting_string_arguments()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $trait = '_generated\\' . $class;
        $this->assertTrue(trait_exists($trait));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingWithStringTwo'));

        $reflectedMethod = $ref->getMethod('step_doSomethingWithStringTwo');

        $parameters = $reflectedMethod->getParameters();

        $this->assertNotEmpty($parameters);
        $this->assertTrue($parameters[0]->name === 'arg1');
        $this->assertTrue($parameters[0]->isOptional());
        $this->assertTrue($parameters[0]->getDefaultValue() === 'foo');
    }

    /**
     * @test
     * it should allow preventing a method from generating a gherking step marking it with @gherkin no
     */
    public function it_should_allow_preventing_a_method_from_generating_a_gherking_step_marking_it_with_gherkin_no()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $trait = '_generated\\' . $class;
        $this->assertTrue(trait_exists($trait));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertFalse($ref->hasMethod('step_noGherkin'));
    }

    /**
     * @test
     * it should translate array parameters to multiple calls to base method
     */
    public function it_should_translate_array_parameters_to_multiple_calls_to_base_method()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleOne',
            '--postfix' => $id
        ]);

        $class = 'ModuleOneGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $trait = '_generated\\' . $class;
        $this->assertTrue(trait_exists($trait));

        $ref = new ReflectionClass('_generated\ModuleOneGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_doSomethingWithArray'));

        $parameters = (new ReflectionMethod($trait, 'step_doSomethingWithArray'))->getParameters();

        /** @var ReflectionParameter $first */
        $first = $parameters[0];
        $this->assertEquals(TableNode::class, $first->getClass()->name);

        $method = '';
        $arguments = [];

        $instance = $this->getInstanceForTrait($trait, $id, $method, $arguments);

        $table = new TableNode([
            ['keyOne', 'keyTwo', 'keyThree'],
            ['foo', 'baz', 'bar'],
            [23, 'foo', 'baz'],
        ]);

        $instance->step_doSomethingWithArray($table);

        $this->assertEquals('doSomethingWithArray', $method);
        $expected = array_map('json_encode', [
            ['keyOne' => 'foo', 'keyTwo' => 'baz', 'keyThree' => 'bar'],
            ['keyOne' => 23, 'keyTwo' => 'foo', 'keyThree' => 'baz'],
        ]);
        $this->assertEquals($expected, $arguments);
    }

    /**
     * @param $trait
     * @param $id
     * @param $method
     * @param $arguments
     */
    protected function getInstanceForTrait($trait, $id, &$method, &$arguments)
    {
        $className = 'ClassUsing_' . $id;
        $classCode = (new Template($this->classTemplate))
            ->place('name', $className)
            ->place('trait', $trait)
            ->produce();

        eval($classCode);

        /** @var Scenario $scenario */
        $scenario = $this->prophesize(Scenario::class);
        $scenario->runStep(Prophecy\Argument::type(Action::class))->will(function (array $args) use (
            &$method,
            &
            $arguments
        ) {
            $action = $args[0];
            $method = $action->getAction();
            $arguments[] = $action->getArgumentsAsString();
        });

        return $instance = new $className($scenario->reveal());
    }

    /**
     * @test
     * it should add placeholders for methods in doc
     */
    public function it_should_add_placeholders_for_methods_in_doc()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleTwo',
            '--postfix' => $id
        ]);

        $class = 'ModuleTwoGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleTwoGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_seeElement'));

        $reflectedMethod = $ref->getMethod('step_seeElement');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Then I see element :name', $methodDockBlock);
    }

    /**
     * @test
     * it should join multiple arguments with and
     */
    public function it_should_join_multiple_arguments_with_and()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleTwo',
            '--postfix' => $id
        ]);

        $class = 'ModuleTwoGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleTwoGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_seeElementWithColor'));

        $reflectedMethod = $ref->getMethod('step_seeElementWithColor');
        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Then I see element :name with color :color', $methodDockBlock);
    }

    /**
     * @test
     * it should mark optional parameters as optional
     */
    public function it_should_mark_optional_parameters_as_optional()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleTwo',
            '--postfix' => $id
        ]);

        $class = 'ModuleTwoGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleTwoGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_seeElementInContext'));

        $reflectedMethod = $ref->getMethod('step_seeElementInContext');

        $parameters = $reflectedMethod->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertTrue($parameters[1]->isDefaultValueAvailable());
        $this->assertNull($parameters[1]->getDefaultValue());

        $methodDockBlock = $reflectedMethod->getDocComment();

        // public function seeElementInContext($context, $text = null)

        $this->assertContains('@Then I see element in context :context', $methodDockBlock);
        $this->assertContains('@Then I see element in context :context and text :text', $methodDockBlock);
    }

    /**
     * @test
     * it should replace words with parameter names when found
     */
    public function it_should_replace_words_with_parameter_names_when_found()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleTwo',
            '--postfix' => $id
        ]);

        $class = 'ModuleTwoGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleTwoGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_haveElementWithColorAndSize'));

        $reflectedMethod = $ref->getMethod('step_haveElementWithColorAndSize');

        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Given I  have element :element with color :color and size :size', $methodDockBlock);
    }

    /**
     * @test
     * it should support complex methods name and optional arguments
     */
    public function it_should_support_complex_methods_name_and_optional_arguments()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleThree',
            '--postfix' => $id
        ]);

        $class = 'ModuleThreeGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleThreeGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_haveShapeWithColorAndSize'));

        $reflectedMethod = $ref->getMethod('step_haveShapeWithColorAndSize');

        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Given I  have shape :shape', $methodDockBlock);
        $this->assertContains('@Given I  have shape :shape with color :color', $methodDockBlock);
        $this->assertContains('@Given I  have shape :shape with color :color and size :size', $methodDockBlock);
    }

    /**
     * @test
     * it should accept a gherkin steps generation configuration file
     */
    public function it_should_accept_a_gherkin_steps_generation_configuration_file()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleFour',
            '--postfix' => $id,
            '--steps-config' => codecept_data_dir('configs/module-4-1.yml')
        ]);

        $class = 'ModuleFourGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleFourGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_haveElementInDatabase'));

        $reflectedMethod = $ref->getMethod('step_haveElementInDatabase');

        $methodDockBlock = $reflectedMethod->getDocComment();

        $this->assertContains('@Given I have element :element in database', $methodDockBlock);
        $this->assertNotContains('@When I have element :element in database', $methodDockBlock);
        $this->assertNotContains('@Then I have element :element in database', $methodDockBlock);
    }

    /**
     * @test
     * it should allow specifying a module method should be skipped in the configuration file
     */
    public function it_should_allow_specifying_a_module_method_should_be_skipped_in_the_configuration_file()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleFive',
            '--postfix' => $id,
            '--steps-config' => codecept_data_dir('configs/module-5-1.yml')
        ]);

        $class = 'ModuleFiveGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleFiveGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_methodOne'));
        $this->assertFalse($ref->hasMethod('step_methodTwo'));
    }

    /**
     * @test
     * it should carry over step definition from original method
     */
    public function it_should_carry_over_step_definition_from_original_method()
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleSix',
            '--postfix' => $id
        ]);

        $class = 'ModuleSixGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleSixGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_haveSomeUser'));
        $this->assertTrue($ref->hasMethod('step_haveSomeRegex'));

        $method = $ref->getMethod('step_haveSomeUser');
        $docBlock = $method->getDocComment();

        $this->assertContains('@Given There is one user in the database', $docBlock);
        $this->assertNotContains('@Given I have some user', $docBlock);
        $this->assertNotContains('@When I have some user', $docBlock);
        $this->assertNotContains('@Then I have some user', $docBlock);

        $method = $ref->getMethod('step_haveSomeRegex');
        $docBlock = $method->getDocComment();

        $this->assertContains('@Given /I have a regex "([^"]*)" set up/', $docBlock);
        $this->assertNotContains('@Given I have some regex', $docBlock);
        $this->assertNotContains('@When I have some regex', $docBlock);
        $this->assertNotContains('@Then I have some regex', $docBlock);
    }

    public function methodsAndNotations()
    {
        return [
            ['amOnPage', 'I am on page :page', false],
            [
                'havePostInDatabase',
                'I have post in database',
                [' :data']
            ]
        ];
    }

    /**
     * @test
     * @dataProvider methodsAndNotations
     * it should generate good defaults
     */
    public function it_should_generate_good_defaults($methodName, $expected, $notExpected = null)
    {
        $app = new Application();
        $this->addCommand($app);
        $command = $app->find('steppify');
        $commandTester = new CommandTester($command);

        $id = uniqid();

        $commandTester->execute([
            'command' => $command->getName(),
            'module' => 'tad\Tests\Modules\ModuleSeven',
            '--postfix' => $id
        ]);

        $class = 'ModuleSevenGherkinSteps' . $id;

        require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

        $this->assertTrue(trait_exists('_generated\\' . $class));

        $ref = new ReflectionClass('_generated\ModuleSevenGherkinSteps' . $id);

        $this->assertTrue($ref->hasMethod('step_' . $methodName));

        $method = $ref->getMethod('step_' . $methodName);
        $docBlock = $method->getDocComment();

        $this->assertContains('@Given ' . $expected, $docBlock);
        if (!empty($notExpected)) {
            foreach ((array)$notExpected as $ne) {
                $this->assertNotRegExp('#@Given .*' . $ne . '.*#', $docBlock);
            }
        }
    }

	/**
	 * It should allow setting the namespace of the generated class
	 *
	 * @test
	 * @dataProvider methodsAndNotations
	 */
	public function should_allow_setting_the_namespace_of_the_generated_class($methodName, $expected, $notExpected = null) {
		$app = new Application();
		$this->addCommand($app);
		$command = $app->find('steppify');
		$commandTester = new CommandTester($command);

		$id = uniqid();

		$commandTester->execute([
			'command' => $command->getName(),
			'module' => 'tad\Tests\Modules\ModuleEight',
			'--namespace' => 'Acme\Project',
			'--postfix' => $id
		]);

		$class = 'ModuleEightGherkinSteps' . $id;

		require_once(Configuration::supportDir() . '_generated/' . $class . '.php');

		$traitFullyQualifiedName = 'Acme\\Project\\_generated\\' . $class;
		$this->assertTrue(trait_exists( $traitFullyQualifiedName ));

		$ref = new ReflectionClass($traitFullyQualifiedName);

		$this->assertTrue($ref->hasMethod('step_' . $methodName));

		$method = $ref->getMethod('step_' . $methodName);
		$docBlock = $method->getDocComment();

		$this->assertContains('@Given ' . $expected, $docBlock);
		if (!empty($notExpected)) {
			foreach ((array)$notExpected as $ne) {
				$this->assertNotRegExp('#@Given .*' . $ne . '.*#', $docBlock);
			}
		}
	}

	/**
	 * It should allow setting the namespace of the generated class from the settings file
	 *
	 * @test
	 * @dataProvider methodsAndNotations
	 */
	public function should_allow_setting_the_namespace_of_the_generated_class_from_the_settings_file($methodName, $expected, $notExpected = null) {
		$app = new Application();
		$this->addCommand($app);
		$command = $app->find('steppify');
		$commandTester = new CommandTester($command);

		$id = uniqid();

		$commandTester->execute([
			'command' => $command->getName(),
			'module' => 'tad\Tests\Modules\ModuleNine',
			'--steps-config' => codecept_data_dir('configs/module-9-1.yml'),
			'--postfix' => $id
		]);

		$class = 'ModuleNineGherkinSteps' . $id;

		$generatedTraitFiilePath = Configuration::supportDir() . '_generated/' . $class . '.php';
		require_once( $generatedTraitFiilePath );

		$traitFullyQualifiedName = 'Acme\\Project\\_generated\\' . $class;
		$this->assertTrue(trait_exists( $traitFullyQualifiedName ));

		$ref = new ReflectionClass($traitFullyQualifiedName);

		$this->assertTrue($ref->hasMethod('step_' . $methodName));

		$method = $ref->getMethod('step_' . $methodName);
		$docBlock = $method->getDocComment();

		$this->assertContains('@Given ' . $expected, $docBlock);
		if (!empty($notExpected)) {
			foreach ((array)$notExpected as $ne) {
				$this->assertNotRegExp('#@Given .*' . $ne . '.*#', $docBlock);
			}
		}
	}

	/**
	 * It should allow namespace option to overrule setting namespace
	 *
	 * @test
	 * @dataProvider methodsAndNotations
	 */
	public function should_allow_namespace_option_to_overrule_setting_namespace($methodName, $expected, $notExpected = null) {
		$app = new Application();
		$this->addCommand($app);
		$command = $app->find('steppify');
		$commandTester = new CommandTester($command);

		$id = uniqid();

		$commandTester->execute([
			'command' => $command->getName(),
			'module' => 'tad\Tests\Modules\ModuleTen',
			'--steps-config' => codecept_data_dir('configs/module-10-1.yml'),
			'--namespace' => 'Acme\Project',
			'--postfix' => $id
		]);

		$class = 'ModuleTenGherkinSteps' . $id;

		$generatedTraitFiilePath = Configuration::supportDir() . '_generated/' . $class . '.php';
		require_once( $generatedTraitFiilePath );

		$traitFullyQualifiedName = 'Acme\\Project\\_generated\\' . $class;
		$this->assertTrue(trait_exists( $traitFullyQualifiedName ));

		$ref = new ReflectionClass($traitFullyQualifiedName);

		$this->assertTrue($ref->hasMethod('step_' . $methodName));

		$method = $ref->getMethod('step_' . $methodName);
		$docBlock = $method->getDocComment();

		$this->assertContains('@Given ' . $expected, $docBlock);
		if (!empty($notExpected)) {
			foreach ((array)$notExpected as $ne) {
				$this->assertNotRegExp('#@Given .*' . $ne . '.*#', $docBlock);
			}
		}
	}

	protected function _before()
	{
		$this->testModules = new FilesystemIterator(codecept_data_dir('modules'),
			FilesystemIterator::CURRENT_AS_PATHNAME);
    }

    protected function _after()
    {
        $pattern = Configuration::supportDir() . '_generated/Module*GherkinSteps*.php';
        foreach (glob($pattern) as $file) {
            unlink($file);
        }
    }

    /**
     * @param $name
     */
    protected function getStepsFileForModule($name)
    {
        $this->moduleStepsFile = Configuration::supportDir() . "_generated/{$name}GherkinSteps.php";

        return $this->moduleStepsFile;
    }
}
