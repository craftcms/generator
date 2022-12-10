<?php

use craft\generator\helpers\Composer;
use craft\helpers\FileHelper;
use yii\base\InvalidArgumentException;

$root = FileHelper::normalizePath(dirname(__DIR__, 2), '/');
$composerFile = "$root/composer.json";
$srcPath = "$root/src";

test('autoloadConfigFromFile', function() use ($composerFile) {
    $autoload = Composer::autoloadConfigFromFile($composerFile);
    expect($autoload)->toHaveKey('craft\\generator\\');
    expect($autoload['craft\\generator\\'])->toBe('src/');
});

test('invalid autoloadConfigFromFile', function() {
    Composer::autoloadConfigFromFile('/nonexistent/composer.json');
})->throws(InvalidArgumentException::class);

test('couldAutoload', function(
    string $dir,
    bool $expectedResult,
    ?array $expectedExistingRoot,
) use ($composerFile) {
    expect(Composer::couldAutoload($dir, $composerFile, $existingRoot))->toBe($expectedResult);
    expect($existingRoot)->toBe($expectedExistingRoot);
})->with([
    [$srcPath, true, ['craft\\generator\\', $srcPath]],
    ["$srcPath/foo/bar", true, ['craft\\generator\\', $srcPath]],
    ["$root/foo/bar", true, null],
    ["$srcPath/foo-bar", false, ['craft\\generator\\', $srcPath]],
    ['/nonexistent/foo/bar', false, null],
]);
