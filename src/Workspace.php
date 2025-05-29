<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator;

use craft\events\RegisterComponentTypesEvent;
use craft\generator\helpers\Code;
use craft\helpers\ArrayHelper;
use Nette\PhpGenerator\Constant;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PsrPrinter;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeAbstract;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor as NodeVisitorInterface;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use yii\base\Event;
use yii\base\InvalidArgumentException;

/**
 * Works on a PHP file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Workspace
{
    /**
     * @var Stmt[]
     */
    private array $newImports = [];

    public function __construct(
        public string $code,
    ) {
    }

    /**
     * Ensures a class is imported, and returns the local class name or alias for it.
     *
     * @param string $class The class to import.
     * @return string The class name or alias that the class should be referred to by.
     */
    public function importClass(string $class): string
    {
        $className = Code::className($class);
        $aliasCount = 0;

        // If the class name matches the class in the code, use an alias
        if (
            preg_match('/^(?:abstract |final )?class (\w+)/m', $this->code, $match) &&
            $match[1] === $className
        ) {
            $aliasCount = 1;
        }

        $this->code = preg_replace_callback('/(^[ \t]*use [^;]+;\s+)+/m', function($match) use (
            $class,
            &$className,
            &$aliasCount,
        ) {
            /** @var Use_[] $stmts */
            $stmts = Code::parseSnippet($match[0]);

            // See if it's already imported
            foreach ($stmts as $use) {
                /** @var UseUse|null $useUse */
                $useUse = ArrayHelper::firstWhere($use->uses, fn(UseUse $useUse) => (string)$useUse->name === $class);
                if ($useUse) {
                    // It's already imported
                    if ($useUse->alias) {
                        $className = (string)$useUse->alias;
                    }
                    return $match[0];
                }
            }

            // Find a unique name for the class
            do {
                $tryClassName = $className . ($aliasCount ? sprintf('Alias%s', $aliasCount !== 1 ? $aliasCount : '') : '');
                $exists = ArrayHelper::contains($stmts, function(Use_ $use) use ($tryClassName) {
                    return ArrayHelper::contains($use->uses, function(UseUse $useUse) use ($tryClassName) {
                        return (string)($useUse->alias ?? $useUse->name->getLast()) === $tryClassName;
                    });
                });
                if ($exists) {
                    $aliasCount++;
                }
            } while ($exists);

            if ($aliasCount) {
                $className = $tryClassName;
            }

            $stmts[] = $this->newImports[] = new Use_([
                new UseUse(new Name($class), $aliasCount ? $className : null),
            ]);

            // Sort the imports
            $this->sortImports($stmts);

            return Code::printSnippet($stmts) . "\n\n";
        }, $this->code, 1, $count);

        if (!$count) {
            if ($aliasCount) {
                $className .= 'Alias';
            }

            // No `use` statements yet
            $this->code = preg_replace_callback('/^namespace.*/m', function($match) use ($class, $className, $aliasCount) {
                $stmt = $this->newImports[] = new Use_([
                    new UseUse(new Name($class), $aliasCount ? $className : null),
                ]);
                return $match[0] . "\n\n" . Code::printSnippet([$stmt]);
            }, $this->code, 1, $count);

            if (!$count) {
                // No `namespace` even
                return "\\$class";
            }
        }

        return $className;
    }

    /**
     * Returns a snippet of all newly-added imports.
     * @return string
     */
    public function printNewImports(): string
    {
        $this->sortImports($this->newImports);
        $snippet = Code::printSnippet($this->newImports);
        return $snippet ? "$snippet\n\n" : '';
    }

    /**
     * Prepares class-level event handler code, and ensures all classes are imported.
     *
     * @param string $class The class that triggers the event.
     * @param string $event The event constant name.
     * @param string $eventClass The event class that will be passed.
     * @param string $handlerCode The event handler code.
     * @param bool $ensureClassExists Whether the event should be wrapped in a `class_exists()` check for `$class`.
     * @return string
     */
    public function prepareEventHandlerCode(
        string $class,
        string $event,
        string $eventClass,
        string $handlerCode,
        bool $ensureClassExists = false,
    ): string {
        $baseEventClassName = $this->importClass(Event::class);
        $className = $this->importClass($class);
        $eventClassName = $this->importClass($eventClass);

        $eventCode = <<<PHP
$baseEventClassName::on($className::class, $className::$event, function($eventClassName \$event) {
    $handlerCode
});
PHP;

        if ($ensureClassExists) {
            $eventCode = <<<PHP
if (class_exists($className::class)) {
    $eventCode
}
PHP;
        }

        return $eventCode;
    }

    /**
     * Prepares class-level event handler code for registering a component, and ensures all classes are imported.
     *
     * @param string $class The class that triggers the registration event.
     * @param string $event The registration event constant name.
     * @param string $componentClass The component class to attach to [[RegisterComponentTypesEvent::$types]].
     * @param bool $ensureClassExists Whether the event should be wrapped in a `class_exists()` check for `$class`.
     * @param string $eventClass The event class that will be passed.
     * @param string $eventProperty The property to register the component to.
     * @return string
     */
    public function prepareRegistrationEventHandlerCode(
        string $class,
        string $event,
        string $componentClass,
        bool $ensureClassExists = false,
        string $eventClass = RegisterComponentTypesEvent::class,
        string $eventProperty = 'types',
    ): string {
        $componentClassName = $this->importClass($componentClass);
        $handlerCode = <<<PHP
\$event->{$eventProperty}[] = $componentClassName::class;
PHP;
        return $this->prepareEventHandlerCode($class, $event, $eventClass, $handlerCode, $ensureClassExists);
    }

    /**
     * Modifies PHP code with a given node visitor.
     *
     * @param NodeVisitorInterface $visitor
     * @return bool Whether the code was modified.
     */
    public function modifyCode(NodeVisitorInterface $visitor): bool
    {
        // Format-preserving pretty printing setup
        // see https://github.com/nikic/PHP-Parser/blob/v5.4.0/doc/component/Pretty_printing.markdown#formatting-preserving-pretty-printing
        $parser = (new ParserFactory())->createForHostVersion();
        $traverser = new NodeTraverser(new CloningVisitor());
        $oldStmts = $parser->parse($this->code);
        $oldTokens = $parser->getTokens();
        $newStmts = $traverser->traverse($oldStmts);

        // Capture the original code
        $printer = new Standard();
        $oldPrint = $printer->prettyPrint($newStmts);

        // Now modify $newStmts
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $newStmts = $traverser->traverse($newStmts);

        if ($printer->prettyPrint($newStmts) === $oldPrint) {
            // Nothing changed
            return false;
        }

        $this->code = $printer->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        return true;
    }

    /**
     * Appends code to an AST node.
     *
     * @param string $code The code to append.
     * @param callable $test A function that determines where to append the code.
     * It will be passed a [[Node]], and should return `true` or `false`.
     * @return bool Whether the code was appended.
     */
    private function appendCode(string|array $code, callable $test): bool
    {
        return $this->modifyCode(new NodeVisitor(
            enterNode: function(Node $node) use ($code, $test): ?int {
                if (property_exists($node, 'stmts') && $test($node)) {
                    if ($node->stmts === null) {
                        $node->stmts = [];
                    }
                    array_push($node->stmts, ...(is_array($code) ? $code : Code::parseSnippet($code)));
                    return NodeTraverser::STOP_TRAVERSAL;
                }
                return null;
            },
        ));
    }

    /**
     * Appends a method or code snippet to a class.
     *
     * @param Method|string $code The PHP code to append to the method.
     * @return bool
     */
    public function appendCodeToClass(Method|string $code): bool
    {
        if ($code instanceof Method) {
            $method = (new PsrPrinter())->printMethod($code);
            $code = <<<PHP
class Foo {
    $method
}
PHP;
            $stmts = Code::parseSnippet($code);
            /** @var Class_ $class */
            $class = $stmts[0];
            $code = $class->stmts;
        }

        return $this->appendCode($code, fn(Node $n) => $this->isClass($n));
    }

    /**
     * Appends a code snippet to a method.
     *
     * @param string $code The PHP code to append to the method.
     * @param string $method The method name to modify.
     * @return bool Whether the code was modified.
     */
    public function appendCodeToMethod(string $code, string $method): bool
    {
        return $this->appendCode($code, fn(Node $n) => $this->isMethod($n, $method));
    }

    /**
     * Modifies a doc comment.
     *
     * @param callable $test
     * @param callable $modify
     * @return bool
     */
    private function modifyDocComment(callable $test, callable $modify): bool
    {
        return $this->modifyCode(new NodeVisitor(
            enterNode: function(Node $node) use ($test, $modify): ?int {
                if ($node instanceof NodeAbstract && $test($node)) {
                    $comment = $node->getDocComment()?->getText() ?? '';
                    $comment = Code::formatDocComment($modify(Code::unformatDocComment($comment)));
                    $node->setDocComment(new Doc($comment));
                    return NodeTraverser::STOP_TRAVERSAL;
                }
                return null;
            },
        ));
    }

    /**
     * Sets a doc comment on an AST node.
     *
     * @param string $comment The comment to set.
     * @param callable $test A function that determines where to append the comment.
     * It will be passed a [[NodeAbstract]], and should return `true` or `false`.
     * @return bool Whether the code was appended.
     */
    private function setDocComment(string $comment, callable $test): bool
    {
        return $this->modifyDocComment($test, fn() => $comment);
    }

    /**
     * Appends a doc comment to an AST node.
     *
     * @param string $comment The comment to append.
     * @param callable $test A function that determines where to append the comment.
     * It will be passed a [[NodeAbstract]], and should return `true` or `false`.
     * @return bool Whether the code was appended.
     */
    private function appendDocComment(string $comment, callable $test): bool
    {
        return $this->modifyDocComment($test, function(string $text) use ($comment) {
            return ($text !== '' ? "$text\n" : '') . $comment;
        });
    }

    /**
     * Sets a doc comment on the class.
     *
     * @param string $comment The comment to set.
     * @return bool
     */
    public function setDocCommentOnClass(string $comment): bool
    {
        return $this->setDocComment($comment, fn($n) => $this->isClass($n));
    }

    /**
     * Appends a doc comment to the class.
     *
     * @param string $comment The comment to append.
     * @return bool
     */
    public function appendDocCommentOnClass(string $comment): bool
    {
        return $this->appendDocComment($comment, fn($n) => $this->isClass($n));
    }

    /**
     * Sets a doc comment on a method.
     *
     * @param string $comment The comment to set.
     * @param string $method The method name.
     * @return bool
     */
    public function setDocCommentOnMethod(string $comment, string $method): bool
    {
        return $this->setDocComment($comment, fn($n) => $this->isMethod($n, $method));
    }

    /**
     * Appends a doc comment to a method.
     *
     * @param string $comment The comment to append.
     * @param string $method The method name.
     * @return bool
     */
    public function appendDocCommentOnMethod(string $comment, string $method): bool
    {
        return $this->appendDocComment($comment, fn($n) => $this->isMethod($n, $method));
    }

    /**
     * Merges new items into an array in the AST, recursively.
     *
     * Integer keys in `$b` will be added to `$a->items` directly.
     *
     * String keys set to nested arrays will be merged recursively.
     *
     * @param Array_ $a The AST array to be merged to.
     * @param array $b The array to be merged from.
     * @throws InvalidArgumentException if `$b` contains any values which aren’t `Expr` objects or nested arrays.
     */
    public function mergeIntoArray(Array_ $a, array $b): void
    {
        foreach ($b as $k => $v) {
            if (is_int($k)) {
                if (!$v instanceof Expr) {
                    throw new InvalidArgumentException('Integer keys must be set to Expr objects.');
                }
                $a->items[] = new ArrayItem($v);
            } else {
                if (!$v instanceof Expr && !is_array($v)) {
                    throw new InvalidArgumentException('String keys must be set to ArrayItem objects or nested arrays.');
                }
                // Does the same key already exist?
                /** @var ArrayItem|null $item */
                $item = ArrayHelper::firstWhere($a->items, fn(?ArrayItem $item) => (
                    $item &&
                    $item->key instanceof String_ &&
                    $item->key->value === $k
                ));
                if ($v instanceof Expr) {
                    if ($item) {
                        $item->value = $v;
                    } else {
                        $a->items[] = new ArrayItem($v, new String_($k));
                    }
                } else {
                    if (!$item) {
                        $item = $a->items[] = new ArrayItem(new Array_([], ['kind' => Array_::KIND_SHORT]), new String_($k));
                    } elseif (!$item->value instanceof Array_) {
                        $item->value = new Array_([], ['kind' => Array_::KIND_SHORT]);
                    }
                    /** @phpstan-ignore-next-line */
                    self::mergeIntoArray($item->value, $v);
                }
            }
        }
    }

    /**
     * Returns whether the given AST node represents a class.
     *
     * @param Node $node
     * @return bool
     */
    public function isClass(Node $node): bool
    {
        return $node instanceof Class_;
    }

    /**
     * Returns whether the given AST node represents a method with a certain name.
     * @param Node $node
     * @param string $method
     * @return bool
     */
    public function isMethod(Node $node, string $method): bool
    {
        return $node instanceof ClassMethod && (string)$node->name === $method;
    }

    private function sortImports(array &$stmts): void
    {
        usort($stmts, fn(Node $a, Node $b) => (string)($a->uses[0]->name ?? '') <=> (string)($b->uses[0]->name ?? ''));
    }
}
