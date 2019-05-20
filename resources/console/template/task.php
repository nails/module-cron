<?php

/**
 * This file is the template for the contents of Cron tasks
 * Used by the console command when creating Cron tasks.
 */

return <<<'EOD'
<?php

/**
 * The {{CLASS_NAME}} Cron task
 *
 * @package  App
 * @category Task
 */

namespace {{NAMESPACE}};

use Nails\Cron\Task\Base;

/**
 * Class {{CLASS_NAME}}
 *
 * @package {{NAMESPACE}}
 */
class {{CLASS_NAME}} extends Base
{
    /**
     * The task description
     *
     * @var string
     */
    const DESCRIPTION = '';
    
    /**
     * The cron expression of when to run
     *
     * @var string
     */
    const CRON_EXPRESSION = '* * * * *';
    
    /**
     * The console command to execute
     *
     * @var string
     */
    const CONSOLE_COMMAND = '';
}

EOD;
