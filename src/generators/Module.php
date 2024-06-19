<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\generator\NodeVisitor;
use craft\generator\Workspace;
use Nette\PhpGenerator\PhpFile;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use yii\base\Module as YiiModule;

/**
 * Creates a new application module.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Module extends BaseGenerator
{
    private string $id;
    private string $targetDir;
    private string $rootNamespace;
    private string $className;
    private bool $bootstrap;

    public function run(): bool
    {
        $this->id = $this->idPrompt('Module ID:', [
            'required' => true,
        ]);

        $this->className = $this->classNamePrompt('Base module class name:', [
            'required' => true,
            'default' => 'Module',
        ]);

        [$this->targetDir, $this->rootNamespace, $addedRoot] = $this->autoloadableDirectoryPrompt('Module location:', [
            'default' => "@root/modules/$this->id",
            'ensureEmpty' => true,
        ]);

        $this->bootstrap = $this->command->confirm('Should the module be loaded during app initialization?');

        if (!file_exists($this->targetDir)) {
            $this->command->createDirectory($this->targetDir);
        }

        // Module class
        $this->writeModuleClass();

        $message = '**Module created!**';

        if ($addedRoot) {
            $message .= "\n\n" . <<<MD
Run the following command to ensure the module gets autoloaded:

```php
> composer dump-autoload
```
MD;
        }

        $appConfigPath = Craft::$app->getConfig()->getConfigFilePath('app');

        if (!$this->modifyFile($appConfigPath, function(Workspace $workspace) {
            $moduleClassName = $workspace->importClass("$this->rootNamespace\\$this->className");

            if (!$workspace->modifyCode(new NodeVisitor(
                enterNode: function(Node $node) use ($workspace, $moduleClassName) {
                    if (!$node instanceof Return_) {
                        return NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN;
                    }
                    // Make sure it an array is returned
                    if (!$node->expr instanceof Array_) {
                        return NodeTraverser::STOP_TRAVERSAL;
                    }
                    $workspace->mergeIntoArray($node->expr, [
                        'modules' => [
                            $this->id => new ClassConstFetch(new Name($moduleClassName), 'class'),
                        ],
                    ]);
                    if ($this->bootstrap) {
                        $workspace->mergeIntoArray($node->expr, [
                            'bootstrap' => [
                                new String_($this->id),
                            ],
                        ]);
                    }
                    return NodeTraverser::STOP_TRAVERSAL;
                }
            ))) {
                return false;
            }

            return true;
        })) {
            $fallbackExample = <<<MD
'modules' => [
    '$this->id' => \\$this->rootNamespace\\$this->className::class,
],
MD;
            if ($this->bootstrap) {
                $fallbackExample .= "\n" . <<<MD
'bootstrap' => [
    '$this->id',
],
MD;
            }

            $message .= "\n\n" . sprintf(
                <<<'MD'
To install%s the module, open `config/app.php` and add the following to the `return` array:

```
%s
```
MD,
                $this->bootstrap ? ' and bootstrap' : '',
                $fallbackExample,
            );
        }
        $this->command->success($message);
        return true;
    }

    private function writeModuleClass(): void
    {
        $file = new PhpFile();
        $file->setStrictTypes($this->command->withStrictTypes);

        $namespace = $file->addNamespace($this->rootNamespace)
            ->addUse(Craft::class)
            ->addUse(YiiModule::class, 'BaseModule');

        $class = $this->createClass($this->className, YiiModule::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $class->getMethod('init')
            ->setReturnType('void');
        $namespace->add($class);

        $name = $this->className !== 'Module' ? $this->className : $this->id;

        $class->setComment(<<<EOD
$name module

@method static $this->className getInstance()
EOD
        );

        $class->addMethod('attachEventHandlers')
            ->setPrivate()
            ->setReturnType('void')
            ->setBody(<<<EOD
// Register event handlers here ...
// (see https://craftcms.com/docs/5.x/extend/events.html to get started)
EOD);

        $this->writePhpClass($namespace);
    }

    private function methods(): array
    {
        $rootAlias = sprintf('@%s', str_replace('\\', '/', $this->rootNamespace));
        $slashedRootNamespace = addslashes($this->rootNamespace);
        return [
            'init' => <<<PHP
Craft::setAlias('$rootAlias', __DIR__);

// Set the controllerNamespace based on whether this is a console or web request
if (Craft::\$app->request->isConsoleRequest) {
    \$this->controllerNamespace = '$slashedRootNamespace\\\\console\\\\controllers';
} else {
    \$this->controllerNamespace = '$slashedRootNamespace\\\\controllers';
}

parent::init();

\$this->attachEventHandlers();

// Any code that creates an element query or loads Twig should be deferred until
// after Craft is fully initialized, to avoid conflicts with other plugins/modules
Craft::\$app->onInit(function() {
    // ...
});
PHP,
        ];
    }
}
