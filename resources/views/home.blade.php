@extends('layouts.app')

@section('page_title') Text Stats - Home @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">

		@if(session()->has('message'))
			{!! session()->get('message') !!}
		@endif
		
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">Text Stats</h2></div>

                <div class="panel-body">
                    
					<p></p>

					@if(isset($documents_analyzed))
					<p>Documents Analyzed: {{ $documents_analyzed }}.</p>
					@endif
					
					@if(isset($words_analyzed))
					<p>Words Analyzed: {{ $words_analyzed }}.</p>
					@endif
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
