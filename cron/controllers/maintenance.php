<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Name:		Cron Maintenance Controller
 *
 * Description:	Holders for common cron jobs
 *
 **/

/**
 * OVERLOADING NAILS' CRON MODULE
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

require_once '_cron.php';

class NAILS_Maintenance extends NAILS_Cron_Controller
{
	public function index()
	{
		/**
		 * @TODO: Running the index method should automatically determine
		 * which jobs should be run. Either it knows that it runs every hour
		 * and tracks what's been going on, or it looks at the time and works
		 * out when the last items were run etc. Think about it.
		 */
	}

	// --------------------------------------------------------------------------


	/**
	 * Hourly maintenance jobs
	 * @return void
	 */
	public function hourly()
	{
		$this->_start( 'maintenance', 'hourly', 'Hourly Maintenance Jobs' );
		$this->runJobs('hourly');
		$this->_end();
	}


	// --------------------------------------------------------------------------


	/**
	 * Daily maintenance jobs
	 * @return void
	 */
	public function daily()
	{
		$this->_start( 'maintenance', 'daily', 'Daily Maintenance Jobs' );
		$this->runJobs('daily');
		$this->_end();
	}


	// --------------------------------------------------------------------------


	/**
	 * Weekly maintenance jobs
	 * @return void
	 */
	public function weekly()
	{
		/**
		 * Possible Weekly Jobs
		 *
		 * - CDN AWS Sourcefile clearout
		 * - Log file zip up and cleanup
		 * - Clear out old CMS Page previews
		 */

		$this->_start( 'maintenance', 'weekly', 'Weekly Maintenance Jobs' );
		$this->runJobs('weekly');
		$this->_end();
	}


	// --------------------------------------------------------------------------


	/**
	 * Monthly maintenance jobs
	 * @return void
	 */
	public function monthly()
	{
		$this->_start( 'maintenance', 'monthly', 'Monthly Maintenance Jobs' );
		$this->runJobs('monthly');
		$this->_end();
	}


	// --------------------------------------------------------------------------


	/**
	 * Runs all jobs which belong to a specific prefix
	 * @param  string $prefix The prefix to run for
	 * @return void
	 */
	protected function runJobs($prefix)
	{
		$class   = new ReflectionClass($this);
		$methods = $class->getMethods();
		$toCall  = array();

		foreach ($methods as $method) {

			if (preg_match('/^' . $prefix . '.+/', $method->name)) {

				$toCall[] = $method->name;
			}
		}

		_LOG('Executing ' . count($toCall) . ' maintenance tasks.');

		foreach ($toCall as $method) {

			_LOG('---');
			_LOG('Calling "' . $method . '()"');
			$this->$method();
		}
	}


	// --------------------------------------------------------------------------


	/**
	 * all shop tasks which need run hourly
	 * @return void
	 */
	protected function hourlyShop()
	{
		if (isModuleEnabled('shop')) {

			_LOG('Shop Module Enabled. Beginning Shop Jobs.');

			// --------------------------------------------------------------------------

			//	Load models
			$this->load->model('shop/shop_model');
			$this->load->model('shop/shop_currency_model');

			// --------------------------------------------------------------------------

			//	Sync Currencies
			_LOG( '... Synching Currencies' );
			if (!$this->shop_currency_model->sync(false)) {

				_LOG('... ... FAILED: ' . $this->shop_currency_model->last_error());
			}

			// --------------------------------------------------------------------------

			_LOG('Finished Shop Jobs');
		}
	}


	// --------------------------------------------------------------------------


	/**
	 * All Sitemap jobs which should be run daily.
	 * @return void
	 */
	public function dailySitemap()
	{
		if (isModuleEnabled('sitemap')) {

			_LOG('Sitemap Module Enabled. Beginning Sitemap Jobs.');

			// --------------------------------------------------------------------------

			//	Load models
			$this->load->model('sitemap/sitemap_model');

			// --------------------------------------------------------------------------

			//	Generate sitemap
			_LOG('... Generating Sitemap data');
			if (!$this->sitemap_model->generate()) {

				_LOG('... ... FAILED: ' . $this->sitemap_model->last_error());
			}

			// --------------------------------------------------------------------------

			_LOG( 'Finished Site Jobs' );
		}
	}


	// --------------------------------------------------------------------------


	/**
	 * All Email tasks which should be run daily.
	 * @return void
	 */
	public function dailyEmail()
	{
		// @TODO: Discard old emails from the archive? Configurable retention?
	}
}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' CRON MODULE
 *
 * The following block of code makes it simple to extend one of the core cron
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_CRON_MAINTENANCE' ) ) :

	class Maintenance extends NAILS_Maintenance
	{
	}

endif;