<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\base\Model as BaseModel;
use craft\generator\BaseGenerator;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Model extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Model name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Model namespace:', [
            'default' => "$this->baseNamespace\\models",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseModel::class);

        $class = $this->createClass($this->className, BaseModel::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName model");

        $this->writePhpClass($namespace);

        $this->command->success("**Model created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'defineRules' => <<<PHP
return array_merge(parent::defineRules(), [
    // ...
]);
PHP,
        ];
    }
}
