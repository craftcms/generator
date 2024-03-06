<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\generator\generators;

use craft\events\DefineBehaviorsEvent;
use craft\generator\BaseGenerator;
use Nette\PhpGenerator\PhpNamespace;
use ReflectionClass;
use yii\base\Behavior as BaseBehavior;
use yii\helpers\Inflector;

/**
 * Creates a new behavior.
 *
 * When a class with an `EVENT_DEFINE_BEHAVIORS` event is provided, the generator will attempt to add an event handler that attaches the behavior.
 */
class Behavior extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private ?string $targetClass = null;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Behavior name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Behavior namespace:', [
            'default' => "$this->baseNamespace\\behaviors",
        ]);

        $this->targetClass = $this->classPrompt('Target class (optional):', [
            'ensureExists' => true,
            'validator' => function(string $class, &$error) {
                if (!class_exists($class)) {
                    $error = "$class does not exist.";
                    return false;
                }
                if (!(new ReflectionClass($class))->hasConstant('EVENT_DEFINE_BEHAVIORS')) {
                    $error = "$class doesn’t define an EVENT_DEFINE_BEHAVIORS event.";
                    return false;
                }
                return true;
            },
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(BaseBehavior::class);

        // `use` the class we want to target to make events and docblocks easier to grok:
        if ($this->targetClass) {
            $namespace->addUse($this->targetClass);
        }

        $class = $this->createClass($this->className, BaseBehavior::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $comment = "$this->displayName behavior";

        // If a class was chosen, add the @property tag:
        if ($this->targetClass) {
            $classParts = explode('\\', $this->targetClass);
            $className = end($classParts);
            $comment .= "\n\n" . "@property $className \$owner";
        }

        $class->setComment($comment);

        $this->writePhpClass($namespace);

        $message = "**Behavior created!**";

        if (
            $this->targetClass &&
            $this->isForModule() &&
            !$this->addRegistrationEventHandlerCode(
                $this->targetClass,
                'EVENT_DEFINE_BEHAVIORS',
                "$this->namespace\\$this->className",
                $fallbackExample,
                false,
                DefineBehaviorsEvent::class,
                'behaviors',
            )
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to attach your new behavior to all instances of `$this->targetClass`:

```
$fallbackExample
```
MD;
        }

        if (!$this->targetClass) {
            $message .= "\n" . <<<MD
You can register your behavior from a component class’s `behaviors()`/`defineBehaviors()` method, or via an `EVENT_DEFINE_BEHAVIORS` event. 
MD;
        }

        $this->command->success($message);
        return true;
    }

    private function methods(): array
    {
        return [
            'events' => <<<PHP
return [
    // ...
];
PHP,
        ];
    }
}
