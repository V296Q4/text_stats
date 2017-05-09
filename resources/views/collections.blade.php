@extends('layouts.app')

@section('page_title') Text Stats - Collections @endsection

@section('main_content')
<div class="container">

	@if(session()->has('message'))
		{!! session()->get('message') !!}
	@endif

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">Your Collections</h2></div>

                <div class="panel-body">
					{!! $table !!}
                </div>
			
				<div class="panel-footer">
					<a class="btn btn-default" href="/create_collection">Create New Collection</a>
				</div>
			
            </div>
        </div>
    </div>
</div>
@endsection
