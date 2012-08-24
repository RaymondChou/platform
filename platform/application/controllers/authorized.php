<?php
/**
 * Part of the Platform application.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Platform
 * @version    1.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2012, Cartalyst LLC
 * @link       http://cartalyst.com
 */

class Authorized_Controller extends Base_Controller
{
	
	/**
	 * Whitelisted auth routes.
	 *
	 * @var  array
	 */
	protected $whitelist = array();

	/**
	 * Called when the class object is
	 * initialized
	 */
	public function __construct()
	{
		$this->filter('before', 'auth')->except($this->whitelist);
		parent::__construct();
	}

}
