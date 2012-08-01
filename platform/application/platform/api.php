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

use Symfony\Component\HttpFoundation\LaravelRequest as RequestFoundation;

/**
 * This Api class acts as the base for basically the entire application.
 *
 * @package     Cartalyst
 * @subpackage  Api
 * @author      Daniel Petrie
 */
class API
{
	// internal api request
	protected static $internal_request = false;

	/**
	 * @var  array  List all supported methods
	 */
	protected static $_supported_formats = array(
		'json'       => 'application/json',
		'serialized' => 'application/vnd.php.serialized',
	);

	/**
	 * Used for making GET API calls {@see call}
	 *
	 * @param   string  The API URI
	 * @param   array   Data to send via the Query String
	 * @return  mixed
	 */
	public static function get($uri, array $data = array())
	{
		return static::call($uri, 'GET', $data);
	}

	/**
	 * Used for making POST API calls {@see call}
	 *
	 * @param   string  The API URI
	 * @param   array   The POST data
	 * @return  mixed
	 */
	public static function post($uri, array $data = array())
	{
		return static::call($uri, 'POST', $data);
	}

	/**
	 * Used for making PUT API calls {@see call}
	 *
	 * @param   string  The API URI
	 * @param   array   The PUT data
	 * @return  mixed
	 */
	public static function put($uri, array $data = array())
	{
		return static::call($uri, 'PUT', $data);
	}

	/**
	 * Used for making DELETE API calls {@see call}
	 *
	 * @param   string  The API URI
	 * @param   array   The DELETE data
	 * @return  mixed
	 */
	public static function delete($uri, array $data = array())
	{
		return static::call($uri, 'DELETE', $data);
	}

	/**
	 * Makes intra-application API requests.
	 *
	 * @param   string  The API URI
	 * @param   string  The Request Type (GET, POST, PUT, DELETE)
	 * @param   array   The data to send with the request
	 * @return  mixed
	 */
	public static function call($uri, $type = 'GET', $data = array())
	{
		$type = strtoupper($type);

		$uri = 'api/'.ltrim($uri, '/');
		list($uri, $format) = (strstr($uri, '.')) ? explode('.', $uri) : array($uri, '');
		$format = (array_key_exists($format, static::$_supported_formats)) ? static::$_supported_formats[$format] : static::$_supported_formats['json'];

		// Store the main request method and data
		$main_request = Request::$foundation;

		// Work out our query string and request data
		$query   = ($type == 'GET')  ? $data : array();
		$request = ($type == 'POST') ? $data : array();

		// Override the server methods
		$server = array_merge($_SERVER, array(
			'REQUEST_METHOD' => strtoupper($type),
			'REQUEST_URI'    => $uri,
		));

		Request::$foundation = new RequestFoundation($query, $request, array(), $_COOKIE, $_FILES, $server);
		Request::foundation()->setMethod($type);
		Request::foundation()->headers->add(array('content-type' => $format));

		// make the api request
		static::$internal_request = true;
		$response = Route::forward($type, $uri);
		static::$internal_request = false;

		if ($format == null)
		{
			return $response->content;
		}

		// now reset to main request
		Request::$foundation = $main_request;

		switch ($format)
		{
			case 'application/json':
				$response = json_decode($response->content, 'assoc');
			break;
			case 'application/vnd.php.serialized':
				$response = unserialize($response->content);
			break;
			default:
				$response = json_decode($response->content, 'assoc');
		}

		return $response;
	}

	public static function is_internal()
	{
		return (bool) static::$internal_request;
	}

}
