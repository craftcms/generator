<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\base\ElementAction as BaseElementAction;
use craft\generator\BaseGenerator;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new element action.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ElementAction extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Element action name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Element action namespace:', [
            'default' => "$this->baseNamespace\\elements\\actions",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseElementAction::class);

        $class = $this->createClass($this->className, BaseElementAction::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName element action");

        $this->writePhpClass($namespace);

        $this->command->success("**Element action created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'getTriggerHtml' => <<<PHP
Craft::\$app->getView()->registerJsWithVars(fn(\$type) => <<<JS
    (() => {
        new Craft.ElementActionTrigger({
            type: \$type,
    
            // Whether this action should be available when multiple elements are selected
            bulk: true,
    
            // Return whether the action should be available depending on which elements are selected
            validateSelection: (selectedItems, elementIndex) => {
              return true;
            },
    
            // Uncomment if the action should be handled by JavaScript:
            // activate: (selectedItems, elementIndex) => {
            //   elementIndex.setIndexBusy();
            //   const ids = elementIndex.getSelectedElementIds();
            //   // ...
            //   elementIndex.setIndexAvailable();
            // },
        });
    })();
JS, [static::class]);

return null;
PHP,
            'performAction' => <<<PHP
\$elements = \$query->all();
// ...
PHP,
        ];
    }
}
