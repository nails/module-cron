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

use Nails\Common\Resource;

/**
 * Class Process
 *
 * @package Nails\Cron\Resource
 */
class Process extends Resource
{
    /** @var int */
    public $id;

    /** @var string */
    public $class;

    /** @var Resource\DateTime */
    public $started;
}
