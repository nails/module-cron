<?php

/**
 * This file is the template for the contents of Cron controllers
 * Used by the console command when creating Cron controllers.
 */

return <<<'EOD'
<?php

/**
 * The {{CLASS_NAME}} Cron controller
 *
 * @package  App
 * @category controller
 */

namespace {{NAMESPACE}};

use Nails\Cron\Controller\Base;

/**
 * Class {{CLASS_NAME}}
 *
 * @package {{NAMESPACE}}
 */
class {{CLASS_NAME}} extends Base
{
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
