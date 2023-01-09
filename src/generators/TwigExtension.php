<?php

namespace craft\generator\generators;

use Craft;
use craft\generator\BaseGenerator;
use craft\generator\helpers\Code;
use craft\generator\Workspace;
use Nette\PhpGenerator\PhpNamespace;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Creates a new Twig extension.
 */
class TwigExtension extends BaseGenerator
{
    private string $className;
    private string $namespace;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Twig extension name:', [
            'default' => 'Extension',
        ]);

        $this->namespace = $this->namespacePrompt('Twig extension namespace:', [
            'default' => "$this->baseNamespace\\web\\twig",
        ]);

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(AbstractExtension::class)
            ->addUse(TwigFilter::class)
            ->addUse(TwigFunction::class)
            ->addUse(TwigTest::class);

        $class = $this->createClass($this->className, AbstractExtension::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment('Twig extension');

        $this->writePhpClass($namespace);

        $message = '**Twig extension created!**';
        if (
            $this->isForModule() &&
            !$this->
            addRegistrationCode($fallbackExample)
        ) {
            $moduleFile = $this->moduleFile();
            $message .= "\n" . <<<MD
Add the following code to `$moduleFile` to register the Twig extension:

```
$fallbackExample
```
MD;
        }

        $this->command->success($message);
        return true;
    }

    private function methods(): array
    {
        return [
            'getFilters' => <<<PHP
// Define custom Twig filters
// (see https://twig.symfony.com/doc/3.x/advanced.html#filters)
return [
    new TwigFilter('passwordify', function(\$string) {
        return strtr(\$string, [
            'a' => '@',
            'e' => '3',
            'i' => '1',
            'o' => '0',
            's' => '5',
        ]);
    }),
    // ...
];
PHP,
            'getFunctions' => <<<PHP
// Define custom Twig functions
// (see https://twig.symfony.com/doc/3.x/advanced.html#functions) 
return [
    new TwigFunction('password', function(\$length = 12) {
        \$chars = '@bcd3fgh1jklmn0pqr5tuvwxyz';
        \$password = '';
        for (\$i = 0; \$i < \$length; \$i++) {
            \$password .= \$chars[rand(0, 25)];
        }
        return \$password;
    }),
    // ...
];
PHP,
            'getTests' => <<<PHP
// Define custom Twig tests
// (see https://twig.symfony.com/doc/3.x/advanced.html#tests)
return [
    new TwigTest('passwordy', function(\$string) {
        \$insecureChars = ['a', 'e', 'i', 'o', 's'];
        foreach (\$insecureChars as \$char) {
            if (str_contains(\$string, \$char)) {
                return false;
            }
        }
        return true;
    }),
    // ...
];
PHP,
        ];
    }

    private function addRegistrationCode(?string &$fallbackExample = null): bool
    {
        $file = $this->findModuleMethod('init');
        if (!$file) {
            return false;
        }

        return $this->modifyFile($file, function(Workspace $workspace) use (
            &$fallbackExample,
        ) {
            $craftClass = $workspace->importClass(Craft::class);
            $extensionClass = $workspace->importClass("$this->namespace\\$this->className");
            $code = <<<PHP
$craftClass::\$app->view->registerTwigExtension(new $extensionClass());
PHP;
            if (!$workspace->appendCodeToMethod($code, 'init')) {
                $fallbackExample = $workspace->printNewImports() . Code::formatSnippet($code);
                return false;
            }

            return true;
        });
    }
}
