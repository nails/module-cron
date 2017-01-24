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

namespace Nails\Cron\App;

use Nails\Cron\Controller\Base;

class {{CLASS_NAME}} extends Base
{
    public function index()
    {   
    }
}

EOD;
