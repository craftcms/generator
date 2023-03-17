<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\base\ElementExporter as BaseElementExporter;
use craft\elements\db\ElementQueryInterface;
use craft\generator\BaseGenerator;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new element exporter.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class ElementExporter extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Element exporter name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Element exporter namespace:', [
            'default' => "$this->baseNamespace\\elements\\exporters",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseElementExporter::class)
            ->addUse(ElementQueryInterface::class);

        $class = $this->createClass($this->className, BaseElementExporter::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName element exporter");

        $this->writePhpClass($namespace);

        $this->command->success(<<<MD
**Element exporter created!**
Register it from your element’s `defineExporters()` method.
MD);
        return true;
    }

    private function methods(): array
    {
        return [
            'displayName' => sprintf('return %s;', $this->messagePhp($this->displayName)),
            'export' => <<<PHP
\$data = [];

foreach (\$query->all() as \$element) {
    // ...
} 

return \$data;
PHP,
        ];
    }
}
