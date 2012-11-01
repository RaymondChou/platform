@layout('templates.default')

<!-- Page Title -->
@section('title')
    {{ Lang::line('platform/settings::general.title') }}
@endsection

<!-- Queue Styles | e.g Theme::queue_asset('name', 'path_to_css', 'dependency')-->

<!-- Styles -->
@section ('styles')
@endsection

{{ Theme::queue_asset('platform-validate', 'js/vendor/platform/validate.js', 'jquery') }}
{{ Theme::queue_asset('bootstrap-tab', 'js/bootstrap/tab.js', 'jquery') }}

<!-- Scripts -->
@section('scripts')

<script>
	$(document).ready(function() {
		Validate.setup($("#general-form"), $("#login-form"), $("#login-form"));
	});
</script>

@endsection

@section('content')
<section id="settings">

    <!-- Tertiary Navigation & Actions -->
    <header class="navbar">
        <div class="navbar-inner">
            <div class="container">

            <!-- .btn-navbar is used as the toggle for collapsed navbar content -->
            <a class="btn btn-navbar" data-toggle="collapse" data-target="#tertiary-navigation">
                <span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
            </a>

            <a class="brand" href="#">{{ Lang::line('platform/settings::general.title') }}</a>

            <!-- Everything you want hidden at 940px or less, place within here -->
            <div id="tertiary-navigation" class="nav-collapse">
                @widget('platform/menus::menus.nav', 2, 1, 'nav nav-pills', ADMIN)
            </div>

            </div>
        </div>
    </header>

    <!-- Quaternary Desktop Navigation -->
    <nav class="quaternary-navigation tabbable visible-desktop">
        <ul class="nav nav-tabs">
            @foreach ( $settings as $extension => $data )
            <li{{ ( $extension === 'platform/settings' ? ' class="active"' : '' ) }}><a href="#tab_{{ str_replace('/', '_', $extension) }}" data-toggle="tab">{{ Lang::line($extension . '::form.settings.legend') }}</a></li>
            @endforeach
        </ul>
    </nav>

    <div class="quaternary page">
         <!-- Quaternary Mobile Navigation -->
        <nav class="hidden-desktop">
            <ul class="nav nav-stacked nav-pills">
                @foreach ( $settings as $extension => $data )
                <li{{ ( $extension === 'platform/settings' ? ' class="active"' : '' ) }}><a href="#tab_{{ str_replace('/', '_', $extension) }}" data-toggle="tab">{{ Lang::line($extension . '::form.settings.legend') }}</a></li>
                @endforeach
            </ul>
        </nav>

        <div class="tab-content">
            @foreach ( $settings as $extension => $data )
            <div class="tab-pane{{ ( $extension === 'platform/settings' ? ' active' : '' ) }}" id="tab_{{ str_replace('/', '_', $extension) }}">
                @widget(str_replace('/', '.', $extension) . '::settings.index', $data)
            </div>
            @endforeach
        </div>
    </div>

</section>
@endsection