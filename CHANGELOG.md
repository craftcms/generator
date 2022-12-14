# Release Notes for Craft Generator

## Unreleased
- The service generator will now modify plugins’ and modules’ `config()` methods even if they are defined by a separate trait or base class, so long as it lives within the plugin/module root path.
- Added `craft\generator\BaseGenerator::findModuleMethod()`.
- Fixed an error that occurred when generating a plugin, if a custom minimum Craft CMS version was entered. ([#1](https://github.com/craftcms/generator/issues/1))

## 1.0.1 - 2022-12-14
- Fixed a bug where new modules weren’t being added to `config/app.php` automatically if no `modules` key existed yet.

## 1.0.0 - 2022-12-13
- Initial release
