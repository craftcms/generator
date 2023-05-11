# Release Notes for Craft Generator

## Unreleased
- Improved the validation error for private plugins’ handle prompts, for handles that don’t begin with an underscore. ([#18](https://github.com/craftcms/generator/issues/18))
- The plugin generator’s “Minimum Craft CMS version” prompt now defaults to the initial minor release of the current Craft version (e.g. `4.4.0` rather than `4.4.11`), if running a stable release of Craft 4.4 or later.
- Generated plugins’ and modules’ `init()` methods now have a `void` return type. ([#20](https://github.com/craftcms/generator/issues/20)) 
- Fixed a bug where it was possible to enter invalid UTF-8 characters into the plugin generator prompts, causing an exception when writing the `composer.json` file.
- Craft CMS 4.4.11+ is now required.

## 1.4.0 - 2023-04-19
- Generated plugins now include a “Create Release” GitHub action, which will create a new GitHub Release whenever the Craft Plugin Store is notified of a new version tag.
- Fixed a PHP error that occurred when generating a private plugin, if a different minimum Craft version was entered.

## 1.3.1 - 2023-03-17
- Fixed a bug where generated elements had a PHP syntax error. ([#15](https://github.com/craftcms/generator/pull/15))

## 1.3.0 - 2023-03-08
- Added support for generating private plugins (requires Craft 4.4.0-beta.1 or later).

## 1.2.2 - 2023-02-03
- Fixed a bug where generated modules weren’t defining a root alias for themselves, leading to an exception getting thrown when running Craft’s `help` command. ([#11](https://github.com/craftcms/generator/issues/11)) 

## 1.2.1 - 2023-02-02
- Fixed a bug where element condition and query classes weren’t getting imported for the element class. ([#13](https://github.com/craftcms/generator/pull/13))
- Fixed a bug where `ecs.php` class imports were missing characters. ([#13](https://github.com/craftcms/generator/pull/13))

## 1.2.0 - 2023-01-11
- Added the GraphQL directive generator (`gql-directive`).
- Fixed a bug where plugins’ `composer.json` would include invalid `require-dev` dependencies if ECS or PHPStan weren’t used. ([#9](https://github.com/craftcms/generator/issues/9))

## 1.1.0 - 2023-01-06
- Added the Twig Extension generator (`twig-extension`). ([#8](https://github.com/craftcms/generator/discussions/8))
- Added support for generating components within arbitrary source paths, via a new `--path` option. 
- Improved the web controller scaffolding.
- Fixed an error that could occur when generating a component for a plugin or module, if the plugin/module class didn’t have an `init()` or `attachEventHandlers()` method.

## 1.0.4 - 2022-12-24
- Plugins’ and modules’ `attachEventHandlers()` methods now include a comment pointing to the [Events documentation](https://craftcms.com/docs/4.x/extend/events.html).

## 1.0.3 - 2022-12-15
- The plugin generator now includes `craft-` in the default suggested Composer package name. ([#5](https://github.com/craftcms/generator/issues/5))
- The module generator now suggests a default root namespace based on root location, if it’s not already autoloadable. ([#2](https://github.com/craftcms/generator/issues/2))
- Fixed a bug where the plugin generator didn’t allow Composer package names that contained numbers. ([#4](https://github.com/craftcms/generator/issues/4)) 

## 1.0.2 - 2022-12-14
- The service generator will now modify plugins’ `config()` method even if it is defined by a separate trait or base class, so long as it lives within the plugin’s root path.
- Added `craft\generator\BaseGenerator::findModuleMethod()`.
- Fixed an error that occurred when generating a plugin, if a custom minimum Craft CMS version was entered. ([#1](https://github.com/craftcms/generator/issues/1))
- Fixed an error that could occur if a plugin or module’s base namespace contained double-backslashes. ([#3](https://github.com/craftcms/generator/pull/3))

## 1.0.1 - 2022-12-14
- Fixed a bug where new modules weren’t being added to `config/app.php` automatically if no `modules` key existed yet.

## 1.0.0 - 2022-12-13
- Initial release
