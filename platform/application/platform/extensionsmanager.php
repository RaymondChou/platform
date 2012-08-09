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

use Laravel\CLI\Command;
use Laravel\Database\Schema;

/**
 * Extension Manager class.
 *
 * @author Ben Corlett
 */
class ExtensionsManager
{

	/**
	 * An array of extensions that are exempt
	 * from being treated like normal extensions.
	 */
	protected $exempt = array();

	/**
	 * Starts all installed extensions with Platform
	 *
	 * @return  Manager
	 */
	public function start_extensions()
	{
		// Get all enabled extensions
		$extensions = $this->enabled();

		// Loop through and start every extension
		foreach ($extensions as $extension)
		{
			$this->start($extension['info']['slug']);
		}

		return $this;
	}

	/**
	 * Starts an extension.
	 *
	 * @param   string  $slug
	 * @param   ExtensionsManager
	 */
	public function start($slug)
	{
		// Load extension info
		$extension = $this->get($slug);

		// Already started?
		if ($bundle = array_get($extension, 'bundles.handles') and Bundle::started($bundle))
		{
			return $this;
		}

		// Register the bundle with Laravel
		if ( ! array_key_exists('bundles', $extension))
		{
			throw new Exception("Every extension.php file must contain a bundles array. None found in [$slug]");
		}

		// Start the bundle
		Bundle::register($slug, $extension['bundles']);
		Bundle::start($slug);

		// Register global routes
		if (array_key_exists('global_routes', $extension))
		{
			// Check we've been given a closure
			if ( ! $extension['global_routes'] instanceof Closure)
			{
				throw new Exception("'global_routes' must be a function / closure in [$slug]");

			}

			$extension['global_routes']();
		}

		// Register listeners
		if (array_key_exists('listeners', $extension))
		{
			// Check we've been given a closure
			if ( ! $extension['listeners'] instanceof Closure)
			{
				throw new Exception("'listeners' must be a function / closure in [$slug]");

			}

			$extension['listeners']();
		}

		return $this;
	}

	/**
	 * Installs an extension by the given slug.
	 *
	 * @param   string  $slug
	 * @param   bool    $enable
	 * @return  Extension
	 */
	public function install($slug, $enable = false)
	{
		// Get extension info
		try
		{
			$info = $this->get($slug);
		}
		catch (Exception $e)
		{
			return false;
		}

		// Create a new model instance.
		$extension = new Extension(array(
			'slug'    => $info['info']['slug'],
			'version' => $info['info']['version'],
			'enabled' => ($is_core = isset($info['info']['is_core'])) ? 1 : (int) $enable,
		));
		$extension->save();

		// We need to start the extension, just in case
		// the migrations that we're about to run require
		// classes that are in the extension. Starting
		// the extension will allow the classes to be autoloaded.
		// An example of this is in the "menus" extension, it
		// uses the "menus" model.
		$this->start($extension->slug);

		// Resolves core tasks.
		require_once path('sys').'cli/dependencies'.EXT;

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_start();

		// Run extensions migration. This will prepare
		// the table we need to install the core extensions
		Command::run(array('migrate', $extension->slug));

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_end_clean();

		// If the newly installed instension is
		// disabled, make sure we disable any menu items
		// that may have been created up on it's install.
		// Disable menus related to the extension
		if ( ! $is_core and ! $enable)
		{
			API::post('menus/disable', array(
				'extension' => $extension->slug,
			));
		}

		return $extension;
	}

	/**
	 * Uninstalls an extension.
	 *
	 * @param   string  $slug
	 * @return  bool
	 */
	public function uninstall($slug)
	{
		$extension = Extension::find($slug);

		if ($extension === null)
		{
			throw new Exception('Platform extension doesn\'t exist.');
		}

		// Resolves core tasks.
		require_once path('sys').'cli/dependencies'.EXT;

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_start();

		/**
		 * @todo Remove - this is a temp fix for the
		 *       problem below.
		 */
		$this->reset_migrations($extension);

		// Reset migrations - loose all data
		// Command::run(array('migrate:reset', $extension->slug));
		// We can't currently do this as Laravel isn't passing the argument
		// for the bundle to reset and thus is caught in an infinite loop.
		/**
		 * @todo Put in bug report for this!
		 */

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_end_clean();

		// Delete reference from the databas
		$extension->delete();

		return true;
	}

	/**
	 * Enables an extension.
	 *
	 * @param   string  $slug
	 * @return  Extension
	 */
	public function enable($slug)
	{
		$extension = Extension::find($slug);

		if ($extension === null)
		{
			throw new Exception('Platform extension [$slug] doesn\'t exist.');
		}

		// Enable menus related to the extension
		API::post('menus/enable', array(
			'extension' => $extension->slug,
		));

		$extension->enabled = 1;
		$extension->save();

		return $extension;
	}

	/**
	 * Disables an extension.
	 *
	 * @param   string  $slug
	 * @return  Extension
	 */
	public function disable($slug)
	{
		$extension = Extension::find($slug);

		if ($extension === null)
		{
			throw new Exception('Platform extension [$slug] doesn\'t exist.');
		}

		// Disable menus related to the extension
		API::post('menus/disable', array(
			'extension' => $extension->slug,
		));

		$extension->enabled = 0;
		$extension->save();

		return $extension;
	}

	/**
	 * Determines if the extension has an update
	 * available or not.
	 *
	 * @param   string  $slug
	 * @return  bool
	 */
	public function has_update($slug)
	{
		// Get the database entity
		$extension = Extension::find($slug);

		// Get the info from the extension.php file
		$info = $this->get_extensionphp($slug);

		// Return 
		return (version_compare($info['info']['version'], $extension->version) > 0);
	}

	/**
	 * Updates an extension.
	 *
	 * @param   string  $slug
	 * @return  bool
	 */
	public function update($slug)
	{
		// Find extension
		$extension = Extension::find($slug);

		// Find extension.php file
		$info = $this->get($extension->slug);

		// Update extension
		$extension->version = $info['info']['version'];

		// Save extension updates
		$extension->save();

		// We need to start the extension, just in case
		// the migrations that we're about to run require
		// classes that are in the extension. Starting
		// the extension will allow the classes to be autoloaded.
		// An example of this is in the "menus" extension, it
		// uses the "menus" model.
		$this->start($extension->slug);

		// Resolves core tasks.
		require_once path('sys').'cli/dependencies'.EXT;

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_start();

		// Run extensions migration. This will prepare
		// the table we need to install the core extensions
		Command::run(array('migrate', $extension->slug));

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_end_clean();

		return $extension;
	}

	/**
	 * Prepares the Platform database for extensions by insuring that
	 * the extensions table is installed in addition to the migrations
	 * table.
	 *
	 * @return  void
	 */
	public function prepare_db_for_extensions()
	{
		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_start();

		// Resolves core tasks.
		require_once path('sys').'cli/dependencies'.EXT;

		// Check for the migrations table
		try
		{
			DB::table('laravel_migrations')->count();
		}
		catch (Exception $e)
		{
			Command::run(array('migrate:install'));
		}

		// Check for the extensions table. The reason
		// this isn't in a migration is simply
		try
		{
			DB::table('extensions')->count();
		}
		catch (Exception $e)
		{
			Schema::create('extensions', function($table)
			{
				$table->increments('id')->unsigned();
				$table->string('slug', 50)->unique();
				$table->text('version', 25);
				$table->boolean('enabled');
			});
		}

		// Just incase the install process got interrupted, start
		// extensions
		$this->start_extensions();

		/**
		 * @todo remove when my pull request gets accepted
		 */
		ob_end_clean();
	}

	/**
	 * Returns all extensions. Installed and uninstalled.
	 *
	 * @return  array
	 */
	public function all()
	{
		$extensions = array();

		// Loop through extensions directories
		foreach ($this->extensions_directories() as $directory)
		{
			// Get our extension slug - always
			// matches the folder name.
			$slug = basename($directory);

			// Read extension info. Always do this even
			// if no details are required as this will
			// validate the extension.
			try
			{
				$extensions[$slug] = $this->get($slug);
			}
			catch (Exception $e)
			{
				continue;
			}
		}

		ksort($extensions);
		return array_values($extensions);
	}

	/**
	 * Returns an array of uninstalled
	 * extensions, with numberic keys, and
	 * where the slug (which is
	 * the folder name of the extension) is the
	 * value. If $detailed is true, then the value
	 * is the extension info.
	 *
	 * @param   mixed  $condition
	 * @return  array
	 */
	public function uninstalled()
	{
		// Array of installed slugs
		$installed_slugs = array();

		// Array of uninstalled extensions
		$extensions = array();

		// Add slugs of installed
		foreach ($this->installed() as $extension)
		{
			$installed_slugs[] = $extension['info']['slug'];
		}

		// Loop through all extensions
		foreach ($this->all() as $extension)
		{
			// Already in the array of installed slugs?
			if (in_array($extension['info']['slug'], $installed_slugs))
			{
				continue;
			}

			$extensions[] = $extension;
		}

		return $extensions;
	}

	/**
	 * Returns all installed extensions as an array
	 * of Extensions\Extenion models.
	 *
	 * @param   mixed  $condition
	 * @return  array
	 */
	public function installed($condition = null)
	{
		$extensions = array();

		foreach (Extension::all($condition) as $extension)
		{
			$extensions[$extension->slug] = $this->get($extension->slug);
		}

		ksort($extensions);
		return array_values($extensions);
	}

	/**
	 * Returns all enabled extensions as an array
	 * of Extensions\Extenion models.
	 *
	 * @return  array
	 */
	public function enabled()
	{
		return $this->installed(function($query)
		{
			return $query->where('enabled', '=', 1);
		});
	}

	/**
	 * Returns all disabled extensions as an array
	 * of Extensions\Extenion models.
	 *
	 * @return  array
	 */
	public function disabled()
	{
		return $this->installed(function($query)
		{
			return $query->where('enabled', '=', 0);
		});
	}

	/**
	 * Returns an array of cascaded extension directories
	 * based on the order of arguments provided.
	 *
	 * Extensions are parsed through the order in which they're
	 * passed to this function.
	 *
	 * @param   mixed
	 * @return  array
	 */
	protected function cascade_extensions_directories()
	{
		// Fallbacks
		$extensions      = array();
		$directories     = array();
		$extension_slugs = array();

		foreach (func_get_args() as $extensions)
		{
			$extensions = (array) $extensions;

			foreach ($extensions as $extension)
			{
				$extension = dirname($extension);

				// Cache the directory slug
				$slug = basename($extension);

				// Only add if it's not already added and it's not
				// in the exempt list
				if ( ! in_array($slug, $extension_slugs) and ! in_array($slug, $this->exempt))
				{
					$directories[]     = $extension;
					$extension_slugs[] = $slug;
				}
			}
		}

		return $directories;
	}

	/**
	 * Returns an array of extensions' directories.
	 *
	 * @todo Determine order of extensions in the groups. For example,
	 *       "Platform" will be loaded last
	 *
	 * @return  array
	 */
	public function extensions_directories()
	{
		$grouped_extensions   = (array) glob(path('extensions').'*'.DS.'*'.DS.'extension'.EXT, GLOB_NOSORT);
		$top_level_extensions = (array) glob(path('extensions').'*'.DS.'extension'.EXT, GLOB_NOSORT);

		return $this->cascade_extensions_directories($top_level_extensions, $grouped_extensions);
	}

	/**
	 * Sorts extension dependencies
	 *
	 * @param   array  $extensions
	 * @return  array  $extensions
	 */
	public function sort_dependencies(&$slugs = array())
	{
		// Array of extensions dependencies, where
		// the key is the slug of the extension
		// and the value is an array of extension slugs
		// on which that extension depends.
		$extensions_dependencies = array();

		foreach ($slugs as $slug)
		{
			try
			{
				$extension = $this->get_extensionphp($slug);
			}
			catch (Exception $e)
			{
				continue;
			}

			if ($dependencies = array_get($extension, 'dependencies') and is_array($dependencies))
			{
				$extensions_dependencies[$slug] = $dependencies;
			}
			else
			{
				$extensions_dependencies[$slug] = array();
			}
		}

		return Dependency::sort($extensions_dependencies);
	}

	/**
	 * Finds the extension file for an extension
	 * with the given slug.
	 *
	 * @param   string  $slug
	 * @return  string
	 */
	public function find_extension_file($slug)
	{
		// We'll search the root dir first
		$files = glob(path('extensions').$slug.DS.'extension'.EXT);

		if (empty($files))
		{
			// We couldn't find the extension file in the first path, so we'll try the 2nd
			$files = glob(path('extensions').'*'.DS.$slug.DS.'extension'.EXT);
		}

		return ( ! empty($files)) ? $files[0] : false;
	}

	/**
	 * Gets an extension by the given slug. Returns
	 * all information for it.
	 *
	 * Note: This method should be used at all
	 *       times externally from this class
	 *       as an API to return an extension. Classes
	 *       should never interact directly with an Extension
	 *       model.
	 *
	 * @param   string  $slug
	 * @return  array   $info
	 */
	public function get($slug)
	{
		// Get the extension.php info
		$extension = $this->get_extensionphp($slug);

		if ($model = Extension::find($slug))
		{
			// Update the version and enabled flags for the extension.
			$extension['info']['version'] = $model['version'];
			$extension['info']['enabled'] = (bool) $model['enabled'];
			$extension['installed']       = true;
			$extension['has_update']      = $this->has_update($slug);
		}

		ksort($extension['info']);
		return $extension;
	}

	/**
	 * Returns the extension's info according
	 * to the extension.php file in the filesystem.
	 *
	 * @param   string  $slug
	 * @return  array   $info
	 */
	public function get_extensionphp($slug)
	{
		$file = $this->find_extension_file($slug);

		// File doesn't exist?
		if ( ! $file)
		{
			throw new Exception("Platform Extension [$slug] doesn't exist.");
		}

		// Info
		$info = require $file;

		// Bunch of requirements for an extension.php file
		if ( ! $info or
			 ! is_array($info) or
			 ! array_get($info, 'info.name') or
			 ! array_get($info, 'info.version'))
		{
			throw new Exception("Platform Excention [$slug] doesn't have a valid extension.php file");
		}

		// Add the slug to the info
		$info['info']['slug'] = $slug;

		// Installed flag
		$info['installed']  = false;
		$info['has_update'] = true;

		// Default parameters
		if ( ! array_key_exists('is_core', $info['info']))
		{
			$info['info']['is_core'] = 0;
		}
		if ( ! array_key_exists('enabled', $info['info']))
		{
			$info['info']['enabled'] = 0;
		}

		ksort($info['info']);
		return $info;
	}

	/**
	 * Temp hotfix around Laravel's migrations limitations.
	 *
	 * @param   Extension  $extension
	 * @return  void
	 */
	protected function reset_migrations(Extension $extension)
	{
		// Start the extension so we can find it's bundle path
		$this->start($extension);

		$files = glob(Bundle::path($extension->slug).'migrations'.DS.'*_*'.EXT);

		// When open_basedir is enabled, glob will return false on an
		// empty directory, so we will return an empty array in this
		// case so the application doesn't bomb out.
		if ($files === false)
		{
			return array();
		}

		// Once we have the array of files in the migration directory,
		// we'll take the basename of the file and remove the PHP file
		// extension, which isn't needed.
		foreach ($files as &$file)
		{
			$file = str_replace(EXT, '', basename($file));
		}

		// We'll also sort the files so that the earlier migrations
		// will be at the front of the array and will be resolved
		// first by this class' resolve method.
		sort($files);

		// Now reverse our sort - migrating down
		$files = array_reverse($files);

		// Loop through files
		foreach ($files as $file)
		{
			require_once Bundle::path($extension->slug).'migrations'.DS.$file.EXT;

			// Since the migration name will begin with the numeric ID, we'll
			// slice off the ID so we are left with the migration class name.
			// The IDs are for sorting when resolving outstanding migrations.
			//
			// Migrations that exist within bundles other than the default
			// will be prefixed with the bundle name to avoid any possible
			// naming collisions with other bundle's migrations.
			$prefix = Bundle::class_prefix($extension->slug);

			$class = $prefix.\Laravel\Str::classify(substr($file, 18));

			$migration = new $class;

			// Run down the migration
			$migration->down();
		}

		// Remove the entry from the migrations table
		DB::table('laravel_migrations')
		  ->where('bundle', '=', $extension->slug)
		  ->delete();

		return $this;
	}

}










/**
 * @todo Maybe put this in it's own file...
 */

class Dependency
{
	public static function sort($extensions = array())
	{
		// The class below requires that we have
		// at least 1 dependency for each module.
		foreach ($extensions as $extension => &$dependencies)
		{
			if (empty($dependencies))
			{
				$dependencies[] = 'core';
			}
		}

		$t = new TopologicalSort($extensions, true);
		$sorted = $t->tsort();

		if ( ! $sorted)
		{
			throw new Exception('Error in sorting dependencies');
		}

		// Search for core (the most basic placehodler
		// dependency we provided)
		if (in_array('core', $sorted))
		{
			// Try keep keys sorted nicely
			if (($key = array_search('core', $sorted)) === 0)
			{
				array_shift($sorted);
			}
			else
			{
				unset($sorted[array_search('core', $sorted)]);
			}
		}

		return $sorted;
	}
}


/**
 * @todo refactor and implement the below class proprly.
 */





/**
* Sorts a series of dependency pairs in linear order
*
* usage:
* $t = new TopologicalSort($dependency_pairs);
* $load_order = $t->tsort();
*
* where dependency_pairs is in the form:
* $name => (depends on) $value
*
*/
class TopologicalSort
{
	public $nodes = array();

	/**
	* Dependency pairs are a list of arrays in the form
	* $name => $val where $key must come before $val in load order.
	*
	*/
	public function __construct($dependencies=array(), $parse=false)
	{
		if ($parse) $dependencies = $this->parseDependencyList($dependencies);
		// turn pairs into double-linked node tree

		foreach($dependencies as $key => $dpair) {
			list($module, $dependency) = each($dpair);

			if (! isset($this->nodes[$module]))
				$this->nodes[$module] = new TSNode($module);

			if (! isset($this->nodes[$dependency]))
				$this->nodes[$dependency] = new TSNode($dependency);

			if (! in_array($dependency,$this->nodes[$module]->children))
				$this->nodes[$module]->children[] = $dependency;

			if (! in_array($module,$this->nodes[$dependency]->parents))
				$this->nodes[$dependency]->parents[] = $module;
		}
	}

	/**
	* Perform Topological Sort
	*
	* @param array $nodes optional array of node objects may be passed.
	* Default is  $this->nodes created in constructor.
	* @return sorted array
	*/
	public function tsort($nodes=array())
	{
		// use this->nodes if it is populated and no param passed
		if (! @count($nodes) && count($this->nodes))
		$nodes = $this->nodes;

		// get nodes without parents
		$root_nodes = array_values($this->getRootNodes($nodes));

		// begin algorithm
		$sorted = array();
		while(count($nodes)>0) {
			// check for circular reference
			if (count($root_nodes) == 0) return false;

			// remove this node from root_nodes
			// and add it to the output
			$n = array_pop($root_nodes);
			$sorted[] = $n->name;

			// for each of its  children
			// queue the new node finally remove the original
			for($i=(count($n->children)-1); $i >= 0; $i--) {
				$childnode = $n->children[$i];
				// remove the link from this node to its
				// children ($nodes[$n->name]->children[$i]) AND
				// remove the link from each child to this
				// parent ($nodes[$childnode]->parents[?]) THEN
				// remove this child from this node
				unset($nodes[$n->name]->children[$i]);
				$parent_position = array_search($n->name,$nodes[$childnode]->parents);
				unset($nodes[$childnode]->parents[$parent_position]);
				// check if this child has other parents
				// if not, add it to the root nodes list
				if (!count($nodes[$childnode]->parents))array_push($root_nodes,$nodes[$childnode]);
			}

			// nodes.Remove(n);
			unset($nodes[$n->name]);
		}
		return $sorted;
	}

	/**
	* Returns a list of node objects that do not have parents
	*
	* @param array $nodes array of node objects
	* @return array of node objects
	*/
	public function getRootNodes($nodes)
	{
	$output = array();
	foreach($nodes as $name => $node)
	 if (!count($node->parents)) $output[$name] = $node;
	return $output;
	}

	/**
	* Parses an array of dependencies into an array of dependency pairs
	*
	* The array of dependencies would be in the form:
	* $dependency_list = array(
	*  "name" => array("dependency1","dependency2","dependency3"),
	*  "name2" => array("dependencyA","dependencyB","dependencyC"),
	*  ...etc
	* );
	*
	* @param array $dlist Array of dependency pairs for use as parameter in tsort method
	* @return array
	*/
	public function parseDependencyList($dlist=array())
	{
	$output = array();
		foreach($dlist as $name => $dependencies)
			foreach($dependencies as $d)
				array_push($output, array($d => $name));
		return $output;
	}
}

/**
* Node class for Topological Sort Class
*
*/
class TSNode
{
	public $name;
	public $children = array();
	public $parents = array();

	public function __construct($name="") {
		$this->name = $name;
	}
}






