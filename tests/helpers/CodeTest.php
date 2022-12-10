<?php

use craft\generator\helpers\Code;
use yii\base\InvalidArgumentException;

test('namespace', function(string $class, ?string $expected) {
    expect(Code::namespace($class))->toBe($expected);
})->with([
    ['Foo', null],
    ['foo\\Bar', 'foo'],
    ['foo\\bar\\Baz', 'foo\\bar'],
]);

test('className', function(string $class, string $expected) {
    expect(Code::className($class))->toBe($expected);
})->with([
    ['Foo', 'Foo'],
    ['foo\\Bar', 'Bar'],
    ['foo\\bar\\Baz', 'Baz'],
]);

test('normalizeClass', function(string $class, string $expected) {
    expect(Code::normalizeClass($class))->toBe($expected);
})->with([
    ['\\foo', 'foo'],
    ['foo/bar/baz', 'foo\\bar\\baz'],
    ['foo\\\\bar//baz///', 'foo\\bar\\baz'],
    ['\\foo\\/bar\\//baz', 'foo\\bar\\baz'],
]);

test('invalid normalizeClass', function(string $class) {
    Code::normalizeClass($class);
})->with([
    ' foo\\bar\\baz',
])->throws(InvalidArgumentException::class);

test('validateClass', function(string $class, bool $expected) {
    expect(Code::validateClass($class))->toBe($expected);
})->with([
    ['foo', true],
    ['foo\\bar\\baz', true],
    ['Foo\\Bar\\Baz', true],
    ['f0o', true],
    ['_foo1_\\_bar2_\\_baz3_', true],
    ['1foo', false],
    ['foo\\2bar\\baz', false],
    ['foo/bar/baz', false],
    ['\\foo', false],
    ['foo\\', false],
    ['foo \\bar\\baz', false],
]);
