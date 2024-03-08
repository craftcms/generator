<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\base\Utility as BaseUtility;
use craft\generator\BaseGenerator;
use craft\services\Utilities;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Utility extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;
    private string $utilityId;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Utility name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Utility namespace:', [
            'default' => "$this->baseNamespace\\utilities",
        ]);

        $this->displayName = Inflector::camel2words($this->className);
        $this->utilityId = Inflector::camel2id($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseUtility::class);

        $class = $this->createClass($this->className, BaseUtility::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName utility");

        $this->writePhpClass($namespace);

        $message = "**Utility created!**";
        if (
            $this->isForModule() &&
            !$this->addRegistrationEventHandlerCode(
                Utilities::class,
                'EVENT_REGISTER_UTILITIES',
                "$this->namespace\\$this->className",
                $fallbackExample,
            )
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the utility:

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
        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'id' => "return '$this->utilityId';",
            'icon' => 'return \'wrench\';',
            'contentHtml' => <<<PHP
// todo: replace with custom content HTML
return '';
PHP,
        ];
    }
}
