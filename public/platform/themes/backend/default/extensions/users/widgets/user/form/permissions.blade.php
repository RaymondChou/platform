{{ Form::open(ADMIN.'/users/permissions/'.$id) }}

	{{ Form::token() }}

	<div class="well">
	@foreach($extension_rules as $category)
		<fieldset>
			<legend>{{ $category['title'] }}</legend>
			@foreach($category['permissions'] as $permission)
				<div>
					<label class="checkbox">
						<input type="checkbox" id="{{ $permission['slug'] }}" name="{{ $permission['slug'] }}" {{ ($permission['has']) ? 'checked="checked"' : '' }}>
						{{ $permission['value'] }}
					</label>
				</div>
			@endforeach
		</fieldset>
	@endforeach
	</div>

	<button class="btn btn-large" type="submit" value="{{ Lang::line('users::users.button.update') }}">{{ Lang::line('users::users.button.update') }}</button>
	<a class="btn btn-large" href="{{ URL::to_secure(ADMIN.'/users') }}">{{ Lang::line('users::users.button.cancel') }}</a>
{{ Form::close() }}
