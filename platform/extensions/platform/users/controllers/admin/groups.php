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

class Users_Admin_Groups_Controller extends Admin_Controller
{

	public $restful = true;

	/**
	 * Admin User Groups Dashboard / Base View
	 *
	 * @return  View
	 */
	public function get_index()
	{
		// get all the input
		$options = Input::get();

		// grab our table data from the user groups api
		$datatable = API::get('users/groups/datatable', $options);

		// format data for passing
		$data = array(
			'columns' => $datatable['columns'],
			'rows'    => $datatable['rows'],
		);

		// if this was an ajax request, only return the body of the table
		if (Request::ajax())
		{
			$data = (json_encode(array(
				"content"        => Theme::make('users::groups.partials.table_groups', $data)->render(),
				"count"          => $datatable['count'],
				"count_filtered" => $datatable['count_filtered'],
				"paging"         => $datatable['paging'],
			)));

			return $data;
		}

		return Theme::make('users::groups.index', $data);
	}

	/**
	 * Create Group
	 *
	 * @return  View
	 */
	public function get_create()
	{
		return Theme::make('users::groups.create', $data = array());
	}

	/**
	 * Create Group Form Processing
	 *
	 * @return  redirect
	 */
	public function post_create()
	{
		// create the group
		$create_group = API::post('users/groups/create', Input::get());

		if ($create_group['status'])
		{
			// group was created - set success and redirect back to admin user groups
			Platform::messages()->success($create_group['message']);
			return Redirect::to('admin/users/groups');
		}
		else
		{
			// there was an error creating the group - set errors
			Platform::messages()->error($create_group['message']);
			return Redirect::to('admin/users/groups/create')->with_input();
		}
	}

	/**
	 * Edit Group Form
	 *
	 * @param   int  group id
	 * @return  View
	 */
	public function get_edit($id = null)
	{
		return Theme::make('users::groups.edit', array('id' => $id));
	}

	/**
	 * Edit Group Form Processing
	 *
	 * @return  Redirect
	 */
	public function post_edit($id = null)
	{
		// initialize data array
		$data = array(
			'id'   => $id,
			'name' => Input::get('name'),
		);

		// update group
		$edit_group = API::post('users/groups/update', $data);

		if ($edit_group['status'])
		{
			// group was edited - set success and redirect back to admin user groups
			Platform::messages()->success($edit_group['message']);
			return Redirect::to(ADMIN.'/users/groups');
		}
		else
		{
			// there was an error editing the group - set errors
			Platform::messages()->error($edit_group['message']);
			return Redirect::to(ADMIN.'/uers/groups/edit/'.$id)->with_input();
		}
	}

	/**
	 * Delete a group - AJAX request
	 *
	 * @param   int     group id
	 * @return  object  json object
	 */
	public function get_delete($id)
	{
		// delete the group
		$delete_group = API::post('users/groups/delete', array('id' => $id));

		if ($delete_group['status'])
		{
			// group was deleted - set success and redirect back to admin user groups
			Platform::messages()->success($delete_group['message']);
			return Redirect::to(ADMIN.'/users/groups');
		}
		else
		{
			// there was an error editing the group - set errors
			Platform::messages()->error($delete_group['message']);
			return Redirect::to(ADMIN.'/users/groups');
		}
	}

	/**
	 * Process permission post
	 *
	 * @return  Redirect
	 */
	public function post_permissions($id)
	{
		if ( ! $id)
		{
			Platform::messages()->error('A group Id is required to update permissions.');
			return Redirect::to(ADMIN.'/users/groups');
		}

		$permissions = Input::get();
		$rules = Sentry\Sentry_Rules::fetch_bundle_rules();

		$update_permissions = array();
		foreach ($permissions as $slug => $permission)
		{
			$bundle_pos = strpos($slug, '_');
			$bundle     = substr($slug, 0, $bundle_pos);
			$rule_slug  = substr($slug, $bundle_pos+1);

			if (isset($rules[$bundle][$rule_slug]))
			{
				$update_permissions[$rules[$bundle][$rule_slug]] = 1;
			}
		}

		// now grab the list of rules
		$rules = Sentry\Sentry_Rules::fetch_rules();

		// and we'll remove them from the user by setting all other rules to empty
		foreach ($rules as $rule)
		{
			if ( ! array_key_exists($rule, $update_permissions))
			{
				$update_permissions[$rule] = '';
			}
		}

		// initialize data array
		$data = array(
			'id'          => $id,
			'permissions' => $update_permissions
		);

		// update group
		$update_group = API::post('users/groups/update', $data);

		if ($update_group['status'])
		{
			// group was updated - set success and redirect back to admin user groups
			Platform::messages()->success($update_group['message']);
			return Redirect::to(ADMIN.'/users/groups');
		}
		else
		{
			// there was an error updating the group - set errors
			Platform::messages()->error($update_group['message']);
			return Redirect::to(ADMIN.'/users/groups/edit/'.$id)->with_input();
		}
	}

}
