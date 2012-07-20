@layout('templates.default')

<!-- Page Title -->
@section('title')
	{{ Lang::line('themes::themes.title') }}
@endsection

<!-- Styles -->
@section('styles')
	@parent
	{{ Theme::asset('themes','themes::css/themes.less') }}
@endsection

<!-- Scripts -->
@section ('scripts')
	@parent
	{{ Theme::asset('themes','themes::js/themes.js') }}
@endsection

<!-- Page Content -->
@section('content')
<section id="themes">

	<header class="row">
			<div class="span6">
				<h1>{{ $theme['name'] }}</h1>
				<p>{{ $theme['description'] }} by {{ $theme['author'] }} v{{ $theme['version'] }}</p>
			</div>
			<nav class="actions span6 pull-right">
			</nav>
	</header>

	<hr>

	<div class="theme row">
		<div class="span12">

			@if (count($theme['options']))
				{{ Form::open(null, 'POST', array('id' => 'theme-options', 'class' => 'form-horizontal')) }}

					{{ Form::token() }}

					<div class="well">
						<input type="hidden" name="theme" value="{{ $theme['dir'] }}">
						@if (isset($theme['id']))
						<input type="hidden" name="id" value="{{ $theme['id'] }}">
						@endif

						@foreach ($theme['options'] as $id => $option)
						<fieldset>
							<legend>{{ $option['text'] }}</legend>
							@foreach ($option['styles'] as $style => $value)
								<label>{{ $style }}</label>
								<input type="text" name="options[{{$id}}][styles][{{$style}}]" value="{{ $value }}">
							@endforeach
						</fieldset>
						@endforeach
					</div>

		            <button class="btn btn-large" type="submit" name="form_options" value="apply">{{ Lang::line('themes::themes.button.apply') }}</button>
		            <a class="btn btn-large" href="../../{{ URI::segment(4) }}">{{ Lang::line('themes::themes.button.complete') }}</a>

				{{ Form::close() }}

			@else

			<div class="unavailable">
				{{ Lang::line('themes::themes.message.no_options') }}
			</div>

			@endif

		</div>
	</div>

</section>

@endsection
