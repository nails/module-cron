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

use Nails\Common\Model\BaseRoutes;

class Routes extends BaseRoutes
{
    /**
     * Returns an array of routes for this module
     *
     * @return array
     */
    public function getRoutes()
    {
        return [
            'cron/(:any)' => 'cron/cronRouter/index',
        ];
    }
}
