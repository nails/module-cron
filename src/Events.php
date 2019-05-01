<?php

/**
 * The class provides a summary of the events fired by this module
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Events
 * @author      Nails Dev Team
 */

namespace Nails\Cron;

use Nails\Common\Events\Base;

class Events extends Base
{
    /**
     * Fired when cron starts
     */
    const CRON_STARTUP = 'CRON:STARTUP';

    /**
     * Fired when cron is ready
     */
    const CRON_READY = 'CRON:READY';
}
