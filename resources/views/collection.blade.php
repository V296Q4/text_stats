@extends('layouts.app')

@section('page_title') Text Stats - Collection @endsection

@section('main_content')
<div class="container">

	@if(session()->has('message'))
		{!! session()->get('message') !!}
	@endif

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">{!! $main_heading !!}</h2></div>

                <div class="panel-body">
                    
					{{ $description or ''}}
					<hr>
					{!! $table !!}
					
                </div>
			
				@if(isset($is_owner) && $is_owner)
				<div class="panel-footer">
					<a class="btn btn-default" href="/collections">Return to Your Collections</a>
				</div>
				@endif
			
            </div>
        </div>
    </div>
</div>
@endsection
