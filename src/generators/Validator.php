<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;
use yii\validators\Validator as BaseValidator;

/**
 * Creates a new validator.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Validator extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Validator name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Validator namespace:', [
            'default' => "$this->baseNamespace\\validators",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseValidator::class);

        $class = $this->createClass($this->className, BaseValidator::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName validator");

        $this->writePhpClass($namespace);

        $this->command->success("**Validator created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'validateValue' => <<<PHP
// todo: implement validation
return null;
PHP,
        ];
    }
}
