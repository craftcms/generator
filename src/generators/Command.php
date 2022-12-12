<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\generator\generators;

use Craft;
use craft\console\Controller;
use craft\generator\BaseGenerator;
use craft\generator\helpers\Code;
use craft\helpers\StringHelper;
use Nette\PhpGenerator\PhpNamespace;
use yii\base\Application;
use yii\console\ExitCode;

/**
 * Creates a new console command.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Command extends BaseGenerator
{
    public function run(): bool
    {
        $relId = $this->idPrompt('Command ID:', [
            'required' => true,
            'allowNesting' => true,
        ]);

        $idParts = explode('/', $relId);
        $id = array_pop($idParts);
        $className = sprintf('%sController', StringHelper::toPascalCase($id));

        $ns = $this->namespacePrompt('Command namespace:', [
            'default' => "$this->baseNamespace\\console\\controllers",
        ]);
        $ns = Code::normalizeClass(sprintf('%s\\%s', $ns, implode('\\', $idParts)));

        $namespace = (new PhpNamespace($ns))
            ->addUse(Craft::class)
            ->addUse(Controller::class)
            ->addUse(ExitCode::class);

        $class = $this->createClass($className, Controller::class, [
            self::CLASS_PROPERTIES => $this->properties(),
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment(sprintf('%s controller', StringHelper::toTitleCase(str_replace('-', ' ', $id))));

        $uniqueId = $this->module instanceof Application ? $relId : sprintf('%s/%s', $this->module->getUniqueId(), $relId);
        $class->addMethod('actionIndex')
            ->setPublic()
            ->setReturnType('int')
            ->setComment("$uniqueId command")
            ->setBody(<<<PHP
// ...
return ExitCode::OK;
PHP);

        $this->writePhpClass($namespace);

        $this->command->success('**Command created!**');
        return true;
    }

    private function properties(): array
    {
        return [
            'defaultAction',
        ];
    }

    private function methods(): array
    {
        return [
            'options' => <<<PHP
\$options = parent::options(\$actionID);
switch (\$actionID) {
    case 'index':
        // \$options[] = '...';
        break;
}
return \$options;
PHP,
        ];
    }
}
