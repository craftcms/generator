# Release Notes for Craft Generator

## 1.1.0 - 2023-01-06
- Added the Twig Extension generator (`twig-extension'). ([#8](https://github.com/craftcms/generator/discussions/8))
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
