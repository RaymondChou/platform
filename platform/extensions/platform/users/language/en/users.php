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

return array(

	/**
	 * NOTE:
	 * check Sentry (application/bundles/sentry) for more lang options concerning users
	 */

	/* General */
	'general' => array(
		'title'                 => 'User Management',
		'description'           => 'manage users, groups, and access rights.',
		'disabled'              => 'Disabled',
		'email'                 => 'Email',
		'enabled'               => 'Enabled',
		'first_name'            => 'First Name',
		'groups'                => 'Groups',
		'id'	                => 'Id',
		'id_required'           => 'A user Id is required.',
		'invalid_login'         => 'Invalid user name or password.',
		'last_name'             => 'Last Name',
		'login'                 => 'Login',
		'new_password'          => 'New Password',
		'not_admin'             => 'Invalid admin user.',
		'not_found'             => 'User not found.',
		'password'              => 'Password',
		'password_confirmation' => 'Confirm Password',
		'password_help'         => 'Leave password fields blank unless you want to change them.',
		'remember_me'           => 'Remember Me',
		'reset_password'        => 'Reset Password',
		'status'                => 'Status',
	),

	/* Buttons */
	'button' => array(
		'create'         => 'Create User',
		'cancel'         => 'Cancel',
		'delete'         => 'Delete',
		'edit'           => 'Edit',
		'login'          => 'Sign In',
		'reset_password' => 'Reset',
		'update'         => 'Save Changes',
	),

	/* Create User */
	'create' => array(
		'error'          => 'User was not created, please try again.',
		'metadata_error' => 'Unable to add user metadata. Please try again.',
		'success'        => 'User created successfully.',
	),

	/* Update User */
	'update' => array(
		'error'          => 'User was not updated, please try again',
		'metadata_error' => 'Unable to add user metadata. Please try again',
		'success'        => 'User updated successfully.',
	),

	/* Delete User */
	'delete' => array(
		'error'   => 'There was an issue deleting the user. Please try again.',
		'success' => 'The user was deleted successfully.',
	),

	/* Reset Password */
	'reset' => array(
		'reset_password_confirm_success' => 'Your password reset has confirmed and updated successfully. You may now log in with your new password.',
		'reset_password_confirm_error'   => 'There was an error confirming your password reset. Please try again.',
		'reset_password_error'           => 'Unable to reset your password, please make sure both Email and Password are set and you are using a registered email address.',
		'reset_password_success'         => 'Your password has been reset, please check your email to confirm.',
	),

	/* Logs */
	'log' => array(
		'create' => 'Created User: :user - Id: :id.',
		'edit'   => 'Edited User: :user - Id: :id.',
		'delete' => 'Deleted User: :user - Id: :id.',
	),

	/* General Errors */
	'errors' => array(
		'count_error' => 'There was an issue retrieving the count, please try again.',
		'invalid_request' => 'Not a valid request.',
	)

);
