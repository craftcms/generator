<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\generator\NodeVisitor;
use craft\generator\Workspace;
use craft\helpers\ArrayHelper;
use Nette\PhpGenerator\PhpNamespace;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeTraverser;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\web\Application;

/**
 * Creates a new service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Service extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;
    private string $componentId;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Service name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Service namespace:', [
            'default' => "$this->baseNamespace\\services",
        ]);

        $this->displayName = Inflector::camel2words($this->className);
        $this->componentId = lcfirst($this->className);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(Component::class);

        $class = $this->createClass($this->className, Component::class);
        $namespace->add($class);

        $class->setComment("$this->displayName service");

        $this->writePhpClass($namespace);

        $message = "**Service created!**";
        if (
            !$this->module instanceof Application &&
            !$this->modifyModuleFile(function(Workspace $workspace) {
                $serviceClassName = $workspace->importClass("$this->namespace\\$this->className");

                if (!$workspace->modifyCode(new NodeVisitor(
                    enterNode: function(Node $node) use ($workspace, $serviceClassName) {
                        if (!$workspace->isMethod($node, 'config')) {
                            return null;
                        }
                        // See if it directly returns an array
                        /** @var ClassMethod $node */
                        /** @var Return_|null $returnStmt */
                        $returnStmt = ArrayHelper::firstWhere($node->stmts, fn(Stmt $stmt) => $stmt instanceof Return_);
                        if (!$returnStmt || !$returnStmt->expr instanceof Array_) {
                            return NodeTraverser::STOP_TRAVERSAL;
                        }
                        if ($returnStmt->expr->items === null) {
                            $returnStmt->expr->items = [];
                        }
                        // Does the array already have a `components` key?
                        /** @var ArrayItem|null $componentsItem */
                        $componentsItem = ArrayHelper::firstWhere(
                            $returnStmt->expr->items,
                            fn(ArrayItem $item) => $item->key instanceof String_ && $item->key->value === 'components'
                        );
                        if ($componentsItem) {
                            $componentsArray = $componentsItem->value;
                            // Make sure it's set to an array
                            if (!$componentsArray instanceof Array_) {
                                return NodeTraverser::STOP_TRAVERSAL;
                            }
                            if ($componentsArray->items === null) {
                                $componentsArray->items = [];
                            } else {
                                // Make sure it doesn't already define a key of the same component ID
                                if (ArrayHelper::contains($componentsArray->items, fn(ArrayItem $item) => (
                                    $item->key instanceof String_ &&
                                    $item->key->value === $this->componentId
                                ))) {
                                    return NodeTraverser::STOP_TRAVERSAL;
                                }
                            }
                        } else {
                            $componentsArray = new Array_();
                            $returnStmt->expr->items[] = new ArrayItem($componentsArray, new String_('components'));
                        }
                        if (str_contains($serviceClassName, '\\')) {
                            $value = new String_($serviceClassName);
                        } else {
                            $value = new ClassConstFetch(new Name($serviceClassName), 'class');
                        }
                        $componentsArray->items[] = new ArrayItem($value, new String_($this->componentId));
                        return NodeTraverser::STOP_TRAVERSAL;
                    }
                ))) {
                    return false;
                }

                $workspace->appendDocCommentOnClass("@property-read $serviceClassName \$$this->componentId");

                return true;
            })
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the service:

```
use $this->namespace\\$this->className;

public static function config(): array
{
    return [
        'components' => [
            '$this->componentId' => $this->className::class,
        ],
    ];
}
```

You should also add a `@property-read` tag to the class’s DocBlock comment, to help with IDE autocompletion:

```
/**
 * @property-read $this->className \$$this->componentId
 */
```
MD;
        }

        $this->command->success($message);
        return true;
    }
}
