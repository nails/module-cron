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

namespace Nails\Routes\Cron;

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
