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
use PhpParser\Lexer\Emulative;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use yii\base\Event;

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
     * @param string $class The class to import
     * @return string The class name or alias that the class should be referred to by
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
                return $class;
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
     * @param string $class The class that triggers the event
     * @param string $event The event constant name
     * @param string $eventClass The event class that will be passed
     * @param string $handlerCode The event handler code
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
     * @param string $class The class that triggers the registration event
     * @param string $event The registration event constant name
     * @param string $componentClass The component class to attach to [[RegisterComponentTypesEvent::$types]]
     * @param bool $ensureClassExists Whether the event should be wrapped in a `class_exists()` check for `$class`.
     * @return string
     */
    public function prepareRegistrationEventHandlerCode(
        string $class,
        string $event,
        string $componentClass,
        bool $ensureClassExists = false,
    ): string {
        $componentClassName = $this->importClass($componentClass);
        $handlerCode = <<<PHP
\$event->types[] = $componentClassName::class;
PHP;
        return $this->prepareEventHandlerCode($class, $event, RegisterComponentTypesEvent::class, $handlerCode, $ensureClassExists);
    }

    /**
     * Modifies PHP code with a given node visitor
     *
     * @param NodeVisitorAbstract $visitor
     * @return bool Whether the code was modified
     */
    public function modifyCode(NodeVisitorAbstract $visitor): bool
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startLine', 'endLine',
                'startTokenPos', 'endTokenPos',
            ],
        ]);

        $parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7, $lexer);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new CloningVisitor());
        $traverser->addVisitor($visitor);

        $oldStmts = $parser->parse($this->code);
        $oldTokens = $lexer->getTokens();
        $newStmts = $traverser->traverse($oldStmts);

        if (Code::printSnippet($oldStmts) === Code::printSnippet($newStmts)) {
            // Nothing changed
            return false;
        }

        $this->code = (new Standard())->printFormatPreserving($newStmts, $oldStmts, $oldTokens);
        return true;
    }

    /**
     * Appends a code snippet to a method.
     *
     * @param string $method The method name to modify
     * @param string $code The PHP code to append to the method
     * @return bool Whether the code was modified
     */
    public function appendToMethod(string $method, string $code): bool
    {
        $visitor = new class($method, Code::parseSnippet($code)) extends NodeVisitorAbstract {
            public function __construct(
                private string $method,
                private array $stmts,
            ) {
            }

            public function enterNode(Node $node): ?int
            {
                if (!$node instanceof ClassMethod || (string)$node->name !== $this->method) {
                    return null;
                }

                array_push($node->stmts, ...$this->stmts);
                return NodeTraverser::STOP_TRAVERSAL;
            }
        };

        return $this->modifyCode($visitor);
    }

    private function sortImports(array &$stmts): void
    {
        usort($stmts, fn(Node $a, Node $b) => (string)($a->uses[0]->name ?? '') <=> (string)($b->uses[0]->name ?? ''));
    }
}
