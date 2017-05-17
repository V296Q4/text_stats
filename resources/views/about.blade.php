@extends('layouts.app')

@section('page_title') Text Stats - About @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">About</h2></div>

                <div class="panel-body">
                    
					<h2>What is {{ config('app.name', 'Text Stats') }}?</h2>
					<p>{{ config('app.name', 'Text Stats') }} analyzes text.  Providing information such as <a>n-grams</a>, <a>lexical density</a>, and <a>word frequency</a>, writers can use the site to compare their work to others.... </p>
					<p>Click Analyze at the top to get started, or Browse to see texts analyzed by other users.
					
					<p>Technology used: <ul><li>LAPP (Linux, Apache, Postgres, PHP)</li><li>DigitalOcean hosting,</li><li>Laravel PHP framework,</li><li>Bootstrap framework,</li><li>Google charts,</li></ul> 
					<p>See the Github page <a href="https://github.com/V296Q4/text_stats/">here</a>.</p>
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
