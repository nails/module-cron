<?php

/**
 * Generates Cron routes
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cron;

use Nails\Common\Interfaces\RouteGenerator;

class Routes implements RouteGenerator
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public static function generate()
    {
        return [
            'cron(/(.+))?' => 'cron/cronRouter/index',
        ];
    }
}
