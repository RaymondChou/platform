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

use Installer\Installer;

class Installer_Index_Controller extends Base_Controller
{

	/**
	 * This function is called before the action is executed.
	 *
	 * @return void
	 */
	public function before()
	{
		parent::before();

		// Always make the system prepared for an install, as
		// we never know which step we're landing on.
		// Installer::prepare();

		// Setup CSS
		Asset::add('bootstrap', 'platform/installer/css/bootstrap.min.css');
		Asset::add('installer', 'platform/installer/css/installer.css');

		// Setup JS
		Asset::add('jquery', 'platform/installer/js/jquery.js');
		Asset::add('url', 'platform/installer/js/url.js');
		Asset::add('bootstrap', 'platform/installer/js/bootstrap.js', array('jquery'));
		Asset::add('installer', 'platform/installer/js/installer.js', array('jquery'));
	}

	public function get_index()
	{

		$data['permissions'] = Installer::permissions();

		return View::make('installer::step_1', $data);
	}

	public function get_step_2()
	{
		// initialize data array
		$data = array(
			'driver'   => null,
			'host'     => null,
			'username' => null,
			'database' => null,
		);

		// check for session data
		$credentials = Installer::get_step_data(2, array());
		foreach ($credentials as $values => $value)
		{
			$data[$values] = $value;
		}

		return View::make('installer::step_2')->with('drivers', Installer::database_drivers())->with('credentials', $data);
	}

	public function post_step_2()
	{
		Installer::remember_step_data(2, Input::get());

		return Redirect::to('installer/step_3');
	}

	public function get_step_3()
	{
		return View::make('installer::step_3');
	}

	public function post_step_3()
	{
		Installer::remember_step_data(3, Input::get());

		return Redirect::to('installer/install');
	}

	public function get_install()
	{
		// 1. Create the database config file
		Installer::create_database_config(Installer::get_step_data(2, function() {
			Redirect::to('installer/step_2')->send();
			exit;
		}));

		// 2. Create a random key
		Installer::generate_key();

		// 3. Install extensions
		Installer::install_extensions();

		// 4. Create user
		$user = Installer::get_step_data(3, function() {
			Redirect::to('installer/step_3')->send();
			exit;
		});

		$create_user = API::post('users/create', array(
			'email'                 => $user['email'],
			'password'              => $user['password'],
			'password_confirmation' => $user['password_confirmation'],
			'groups'                => array('admin', 'users'),
			'metadata'              => array(
				'first_name' => $user['first_name'],
				'last_name'  => $user['last_name'],
			),
			'permissions' => array(
				Config::get('sentry::sentry.permissions.superuser') => 1,
			),
		));

		if ( ! $create_user['status'])
		{
			return Redirect::to('installer/step_3');
		}

		return Redirect::to('installer/step_4');
	}

	public function get_step_4()
	{
		Session::forget('installer');

		return View::make('installer::step_4')
		           ->with('key', Config::get('application.key'));
	}

	/**
	 * Confirm database - Step 1
	 *
	 * @return  Response
	 */
	public function post_confirm_db()
	{
		if ( ! Request::ajax())
		{
			return Event::fire('404');
		}

		try
		{
			Installer::check_database_connection(array(
				'driver'   => Input::get('driver'),
				'host'     => Input::get('host'),
				'database' => Input::get('database'),
				'username' => Input::get('username'),
				'password' => Input::get('password'),
			));
		}
		catch (Exception $e)
		{
			// Error 1146 is actually good, because it
			// means we connected fine, just couldn't
			// get the contents of the random table above.
			// For some reason this exception has a code of "0"
			// whereas all of the other exceptions match the
			// database errors. Life goes on.
			if ($e->getCode() !== 0)
			{
				return new Response(json_encode(array(
					'error'   => true,
					'message' => $e->getMessage(),
				)));
			}
		}

		return json_encode(array(
			'error'   => false,
			'message' => 'Successfully connected to the database',
		));
	}

	/**
	 * Catch-all method for requests that can't be matched.
	 *
	 * @param  string    $method
	 * @param  array     $parameters
	 * @return Response
	 */
	public function __call($method, $parameters)
	{
		return $this->get_index();
	}

}
