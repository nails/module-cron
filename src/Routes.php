<?php

/**
 * Generates Cron routes
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Routes
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cron;

use Nails\Common\Interfaces\RouteGenerator;

/**
 * Class Routes
 *
 * @package Nails\Cron
 */
class Routes implements RouteGenerator
{
    /**
     * Returns an array of routes for this module
     *
     * @return string[]
     */
    public static function generate(): array
    {
        return [
            'cron(/(.+))?' => 'cron/cronRouter/index',
        ];
    }
}
