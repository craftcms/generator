<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator\generators;

use Craft;
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;
use craft\generator\BaseGenerator;
use craft\helpers\StringHelper;
use Nette\PhpGenerator\PhpNamespace;
use yii\helpers\Inflector;

/**
 * Creates a new Active Record model.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Record extends BaseGenerator
{
    private string $className;
    private string $namespace;
    private string $displayName;
    private string $tableName;

    public function run(): bool
    {
        $this->className = $this->classNamePrompt('Record name:', [
            'required' => true,
        ]);

        $this->namespace = $this->namespacePrompt('Record namespace:', [
            'default' => "$this->baseNamespace\\records",
        ]);

        $this->displayName = Inflector::camel2words($this->className);

        $this->tableName = $this->command->prompt('The table name that instances should be stored in:', [
            'required' => true,
            'pattern' => '/^\w+$/',
            'default' => sprintf('%s%s',
                $this->isForModule() ? "{$this->module->id}_" : '',
                strtolower(Inflector::pluralize($this->className)
            )),
        ]);
        $db = Craft::$app->getDb();
        if ($db->tablePrefix) {
            $this->tableName = StringHelper::removeLeft($this->tableName, $db->tablePrefix);
        }
        $this->tableName = "{{%$this->tableName}}";

        $table = $db->getTableSchema($this->tableName);
        if ($table) {
            $softDeletable = isset($table->columns['dateDeleted']);
        } else {
            $softDeletable = $this->command->confirm('Will instances be soft-deletable? (requires a `dateDeleted` column)');
        }

        $namespace = (new PhpNamespace($this->namespace))
            ->addUse(Craft::class)
            ->addUse(ActiveRecord::class);

        if ($softDeletable) {
            $namespace->addUse(SoftDeleteTrait::class);
        }

        $class = $this->createClass($this->className, ActiveRecord::class, [
            self::CLASS_METHODS => $this->methods(),
        ]);
        $namespace->add($class);

        $class->setComment("$this->displayName record");

        if ($table && !empty($table->columns)) {
            $class->addComment('');
            foreach ($table->columns as $name => $column) {
                $type = match ($column->phpType) {
                    'integer' => 'int',
                    'double' => 'float',
                    default => $column->phpType,
                };
                if ($column->allowNull) {
                    $type .= '|null';
                }
                $description = ucfirst(Inflector::camel2words($name, false));
                $description = str_replace('_', ' ', $description);
                $description = preg_replace('/\bid\b/i', 'ID', $description);
                $class->addComment("@property $type $$name $description");
            }
        }

        if ($softDeletable) {
            $class->addTrait(SoftDeleteTrait::class);
        }

        $this->writePhpClass($namespace);

        $this->command->success("**Record created!**");
        return true;
    }

    private function methods(): array
    {
        return [
            'tableName' => "return '$this->tableName';",
        ];
    }
}
