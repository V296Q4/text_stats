@extends('layouts.app')

@section('page_title') Text Stats - Document @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-body">
					<div style="white-space:pre-wrap">{{ $main_text }}</div>
                </div>
				
				<div class="panel-footer">
					<a class="btn btn-default" href="{!! $return_url !!}">Return</a>
				</div>
            </div>
        </div>
    </div>
</div>
@endsection
