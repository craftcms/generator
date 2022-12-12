<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\helpers\StringHelper;
use craft\web\AssetBundle as BaseAssetBundle;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new asset bundle.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class AssetBundle extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Asset bundle name:', [
            'required' => true,
        ]);
        $this->className = StringHelper::ensureRight($this->className, 'Asset');

        $this->namespace = $this->namespacePrompt('Asset bundle namespace:', [
            'default' => sprintf(
                '%s\\web\\assets\\%s',
                $this->baseNamespace,
                strtolower(StringHelper::removeRight($this->className, 'Asset'))
            ),
        ]);

        $this->displayName = Inflector::camel2words(StringHelper::removeRight($this->className, 'Asset'));

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseAssetBundle::class);

        $class = $this->createClass($this->className, BaseAssetBundle::class, [
            self::CLASS_PROPERTIES => $this->properties(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName asset bundle");

        $this->writePhpClass($namespace);

        $basePath = $this->namespacePath($this->namespace);
        $this->command->createDirectory("$basePath/dist");
        $this->command->createDirectory("$basePath/src");

        $this->command->success('**Asset bundle created!**');
        return true;
    }

    private function properties(): array
    {
        return [
            'sourcePath' => new Literal("__DIR__ . '/dist'"),
            'depends',
            'js',
            'css',
        ];
    }
}
