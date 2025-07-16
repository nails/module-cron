<?php

/**
 * Process Resource
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Resources
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Resource;

use Nails\Common\Resource\Entity;

/**
 * Class Process
 *
 * @package Nails\Cron\Resource
 */
class Process extends Entity
{
    /** @var string */
    public $class;

    /** @var Resource\DateTime */
    public $started;
}
