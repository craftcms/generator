<?php

use craft\test\TestSetup;

define('CRAFT_VENDOR_PATH', dirname(__DIR__) . '/vendor');
const CRAFT_TESTS_PATH = CRAFT_VENDOR_PATH . '/craftcms/cms/tests/_craft';
const CRAFT_CONFIG_PATH = CRAFT_TESTS_PATH . '/config';
const CRAFT_MIGRATIONS_PATH = CRAFT_TESTS_PATH . '/migrations';
const CRAFT_STORAGE_PATH = CRAFT_TESTS_PATH . '/storage';
const CRAFT_TEMPLATES_PATH = CRAFT_TESTS_PATH . '/templates';
const CRAFT_TRANSLATIONS_PATH = CRAFT_TESTS_PATH . '/translations';

TestSetup::configureCraft();
