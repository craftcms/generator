<h1 align="center">
  <div><img src="./icon.svg" width="100" height="100" alt="Craft Generator icon"></div>
  Craft Generator
</h1>

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
php craft make gql-directive
php craft make model
php craft make module
php craft make plugin
php craft make queue-job
php craft make record
php craft make service
php craft make twig-extension
php craft make utility
php craft make validator
php craft make widget-type
```

All component generation commands require one of the following options to be passed, which identify where the component is going to live:

- `--app`
- `--module=<module-id>`
- `--plugin=<plugin-handle>`

For example, if you’re creating a new field type for a plugin called `foo-bar`, you would run:

```sh
php craft make field-type --plugin=foo-bar
```

## Creating custom generators

If you have a plugin that has its own component type that could benefit from a custom generator, you can quickly create one with the following command:

```sh
php craft make generator --plugin=<plugin-handle>
```

You’ll be presented with the following prompts:

- **Generator name**: Your generator’s class name (sans namespace)
- **Generator namespace**: The namespace your generator class should live in
- **Base class for generated [type]**: An existing base class which generated classes should extend
- **Default namespace for generated [type]**: The default namespace which the generator should suggest, relative to the plugin/module’s root namespace

Your generator will be created based on the provided class name and namespace, which extends [`craft\generator\BaseGenerator`](src/BaseGenerator.php).

## Roadmap

The following generator types are being considered for future releases:

- [ ] Events
- [ ] Exceptions
- [ ] GraphQL arguments
- [x] GraphQL directives
- [ ] GraphQL interfaces
- [ ] GraphQL mutations
- [ ] GraphQL queries
- [ ] GraphQL resolvers
- [ ] GraphQL types
- [ ] Migrations
- [ ] Tests
