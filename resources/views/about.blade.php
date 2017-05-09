@extends('layouts.app')

@section('page_title') Text Stats - Home @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">About</h2></div>

                <div class="panel-body">
                    
					<h2>What is {{ config('app.name', 'Text Stats') }}?</h2>
					<p>{{ config('app.name', 'Text Stats') }} analyzes text.  Providing information such as <a>n-grams</a>, <a>lexical density</a>, and <a>word frequency</a>, writers can use the site to compare their work to others.... 
					
					<p>something something analyze text</p>

					<p>something something tech: LAPP, DO, Laravel PHP framework, google charts, bootstrap, 
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
