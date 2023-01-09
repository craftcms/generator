<?php

namespace craft\generator\generators;

use Craft;
use craft\events\RegisterGqlDirectivesEvent;
use craft\generator\BaseGenerator;
use craft\gql\base\Directive;
use craft\gql\GqlEntityRegistry;
use craft\services\Gql;
use GraphQL\Language\DirectiveLocation;
use GraphQL\Type\Definition\Directive as BaseGqlDirective;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new GraphQL directive.
 */
class GqlDirective extends BaseGenerator
{
    private string $name;
    private string $namespace;
    private string $description;
    private string $className;
    private string $displayName;

    public function run(): bool
    {
        $this->name = $this->command->prompt('Directive name: (camelCase)', [
            'required' => true,
            'pattern' => '/^[a-z]\w*$/',
        ]);

        $this->namespace = $this->namespacePrompt('Directive namespace:', [
            'default' => "$this->baseNamespace\\gql\\directives",
        ]);

        $this->description = addslashes($this->command->prompt('Directive description:'));

        $this->className = Inflector::camelize($this->name);
        $this->displayName = Inflector::camel2words($this->name);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(Directive::class)
            ->addUse(BaseGqlDirective::class, 'GqlDirective')
            ->addUse(DirectiveLocation::class)
            ->addUse(FieldArgument::class)
            ->addUse(GqlEntityRegistry::class)
            ->addUse(ResolveInfo::class)
            ->addUse(Type::class);

        $class = $this->createClass($this->className, Directive::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName GraphQL directive");

        $this->writePhpClass($namespace);

        $message = '**Directive created!**';
        if (
            $this->isForModule() &&
            !$this->addRegistrationEventHandlerCode(
                Gql::class,
                'EVENT_REGISTER_GQL_DIRECTIVES',
                "$this->namespace\\$this->className",
                $fallbackExample,
                eventClass: RegisterGqlDirectivesEvent::class,
                eventProperty: 'directives',
            )
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the directive:

```
$fallbackExample
```
MD;
        }

        $this->command->success($message);
        return true;
    }

    private function methods(): array
    {
        // List any methods that should be copied into generated gql directives from craft\gql\base\Directive
        // (see `craft\generator\BaseGenerator::createClass()`)
        return [
            'create' => <<<PHP
if (\$type = GqlEntityRegistry::getEntity(self::name())) {
    return \$type;
}

return GqlEntityRegistry::createEntity(static::name(), new self([
    'name' => static::name(),
    'locations' => [
        DirectiveLocation::FIELD,
    ],
    'args' => [
        new FieldArgument([
            'name' => 'myArg',
            'type' => Type::string(),
            'defaultValue' => null,
            'description' => 'Argument description',
        ]),
    ],
    'description' => '$this->description',
]));
PHP,
            'name' => <<<PHP
return '$this->name';
PHP,
            'apply' => <<<PHP
// Modify \$value...
return \$value;
PHP,
        ];
    }
}
