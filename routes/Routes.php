<?php

namespace Nails\Routes\Cron;

/**
 * Generates Cron routes
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class Routes
{
    /**
     * Returns an array of routes for this module
     * @return array
     */
    public function getRoutes()
    {
        $routes = array();
        $routes['cron/(:any)'] = 'api/cronRouter/index';
        return $routes;
    }
}
