<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\generator\generators;

use craft\events\DefineBehaviorsEvent;
use Nette\PhpGenerator\PhpNamespace;
use craft\generator\BaseGenerator;
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
                if (defined("{$class}::EVENT_DEFINE_BEHAVIORS")) {
                    return true;
                }

                $error = 'You must choose a class that emits an `EVENT_DEFINE_BEHAVIORS` event in order to automatically attach Behaviors to it.';

                return false;
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
            $comment .= "\n\n" . "@property $this->targetClass \$owner";
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
Your new behavior was not configured to be attached to any classes. You may need to add custom event logic to register it with the system.
MD;
        }

        $this->command->success($message);
        return true;
    }

    private function methods(): array
    {
        return [
            'events' => $this->targetClass ? <<<PHP
return [
    // {$this->targetClass}::EVENT_INIT => [\$this, 'myInitMethod'],
];
PHP : 'return [];',
        ];
    }
}
