# Release Notes for Craft Generator

## 1.0.2 - 2022-12-14
- The service generator will now modify plugins’ `config()` method even if it is defined by a separate trait or base class, so long as it lives within the plugin’s root path.
- Added `craft\generator\BaseGenerator::findModuleMethod()`.
- Fixed an error that occurred when generating a plugin, if a custom minimum Craft CMS version was entered. ([#1](https://github.com/craftcms/generator/issues/1))
- Fixed an error that could occur if a plugin or module’s base namespace contained double-backslashes. ([#3](https://github.com/craftcms/generator/pull/3))

## 1.0.1 - 2022-12-14
- Fixed a bug where new modules weren’t being added to `config/app.php` automatically if no `modules` key existed yet.

## 1.0.0 - 2022-12-13
- Initial release
