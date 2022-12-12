<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\queue\BaseJob;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new queue job.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class QueueJob extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Queue job name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Queue job namespace:', [
            'default' => "$this->baseNamespace\\jobs",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(BaseJob::class);

        $class = $this->createClass($this->className, BaseJob::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName queue job");

        $this->writePhpClass($namespace);

        $this->command->success("**Queue job created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'execute' => '// ...',
            'defaultDescription' => 'return null;',
        ];
    }
}
