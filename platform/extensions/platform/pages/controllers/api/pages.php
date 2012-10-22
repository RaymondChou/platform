<?php

use Platform\Pages\Model\Page;

class Pages_Api_Pages_Controller extends API_Controller
{
	public function get_index($id = false)
	{
		try
		{
			if ($id == false)
			{
				$content = Page::all();
			}
			elseif (is_numeric($id))
			{
				$content = Page::find($id);
			}
			else
			{
				$content = Page::find(function($query) use ($id) {
					return $query->where('slug', '=', $id);
				});
			}

			if ($content)
			{
				return new Response($content);
			}

			return new Response('Could not find content', API::STATUS_NOT_FOUND);
		}
		catch (Exception $e)
		{
			return new Response('Could not find content', API::STATUS_NOT_FOUND);
		}
	}

	public function post_index()
	{
		// Get the Post Data
		//
		$content = new Page(Input::get());

		try
		{
			if ($content->save())
			{
				return new Response($content, API::STATUS_CREATED);
			}

			return new Response(array(
				'message' => 'Unable to Create Page',
				'errors'  => ($content->validation()->errors->has()) ? $content->validation()->errors->all() : array(),
				), ($content->validation()->errors->has()) ? API::STATUS_BAD_REQUEST : API::STATUS_UNPROCESSABLE_ENTITY);
		}
		catch (Exception $e)
		{
			return new Response(array(
				'message' => $e->getMessage(),
			), API::STATUS_BAD_REQUEST);
		}
	}

	public function put_index($id)
	{
		$content = new Page(array_merge(
			array('id' => $id), Input::get()
		));

		try
		{
			if ($content->save())
			{
				return new Response($content);
			}

			return new Response(array(
					'message' => 'Could not update content.',
					'errors'  => ($content->validation()->errors->has()) ? $content->validation()->errors->all() : array(),
				), ($content->validation()->errors->has()) ? API::STATUS_BAD_REQUEST : API::STATUS_UNPROCESSABLE_ENTITY);
		}
		catch (Exception $e)
		{
			return new Response(array(
				'message' => $e->getMessage(),
			), API::STATUS_BAD_REQUEST);
		}
	}

	public function delete_index($id)
	{
		try
		{
			$content = Page::find($id);

			if ($content === null)
			{
				return new Response(array(
					'message' => 'Page does not exist.'
				), API::STATUS_NOT_FOUND);
			}

			if ($content->delete())
			{
				return new Response(null, API::STATUS_NO_CONTENT);
			}

			return new Response(array(
				'message' => 'There was an error deleting the content.',
				'errors'  => ($content->validation()->errors->has()) ? $content->validation()->errors->all() : array(),
			), ($content->validation()->errors->has()) ? API::STATUS_BAD_REQUEST : API::STATUS_UNPROCESSABLE_ENTITY);
		}
		catch (Exception $e)
		{
			return new Response(array(
				'message' => $e->getMessage(),
			), API::STATUS_BAD_REQUEST);
		}
	}

	public function get_datatable()
	{
		$defaults = array(
			'select'    => array(
				'id'       => 'Id',
				'name'     => 'Name',
				'slug'     => 'Slug',
				'template' => 'Template',
				'status'   => 'Status',
			),
			'alias'     => array(),
			'where'     => array(),
			'order_by'  => array('id' => 'desc'),
		);

		// lets get to total user count
		$count_total = Page::count();

		// get the filtered count
		// we set to distinct because a user can be in multiple groups
		$count_filtered = Page::count('id', false, function($query) use ($defaults)
		{
			// sets the where clause from passed settings
			$query = Table::count($query, $defaults);

			return $query;
		});

		// set paging
		$paging = Table::prep_paging($count_filtered, 20);

		$items = Page::all(function($query) use ($defaults, $paging)
		{
			list($query, $columns) = Table::query($query, $defaults, $paging);

			return $query
				->select($columns);

		});

		$items = ($items) ?: array();

		return new Response(array(
			'columns'        => $defaults['select'],
			'rows'           => $items,
			'count'          => $count_total,
			'count_filtered' => $count_filtered,
			'paging'         => $paging,
		));
	}
}