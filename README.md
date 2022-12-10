# Craft Generator

Scaffold new Craft CMS plugins, modules, and system components from the CLI.

## Installation

To install, run the following command within a Craft project:

```sh
composer require craftcms/generator --dev
```

> **Note**
> If you get the following prompt, make sure to answer `y`:
>
> ```sh
> yiisoft/yii2-composer contains a Composer plugin which is currently not in your allow-plugins config. See https://getcomposer.org/allow-plugins
> Do you trust "yiisoft/yii2-composer" to execute code and wish to enable it now? (writes "allow-plugins" to composer.json)
> ```

## Usage

Run the following command to output the usage instructions:

```sh
php craft make
```

### Plugin and module generation

You can create new plugins and modules using the following commands:

```sh
php craft make plugin
php craft make module
```

### System component generation

You can create new system components using the following commands:

```sh
php craft make asset-bundle
php craft make command
php craft make controller
php craft make element-action
php craft make element-condition-rule
php craft make element-exporter
php craft make element-type
php craft make field-type
php craft make filesystem-type
php craft make generator
php craft make module
php craft make plugin
php craft make queue-job
php craft make widget-type
```

All component commands require one of the following options to be passed:

- `--app`
- `--module=<module-id>`
- `--plugin=<plugin-handle>`

## Creating custom generators

If you have a plugin that has its own component type that could benefit from a custom generator, you can quickly create one with the following command:

```sh
php craft make generator --plugin=<plugin-handle>
```

You’ll be guided through 
