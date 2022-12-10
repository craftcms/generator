<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license MIT
 */

namespace craft\generator;

use yii\base\BootstrapInterface;
use yii\console\Application as ConsoleApp;

/**
 * Generator Yii2 Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 */
class Extension implements BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof ConsoleApp) {
            $app->controllerMap['make'] = Command::class;
        }
    }
}
