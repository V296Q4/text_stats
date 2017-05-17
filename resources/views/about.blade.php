@extends('layouts.app')

@section('page_title') Text Stats - About @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">About</h2></div>
                <div class="panel-body">
					<h3>What is {{ config('app.name', 'Text Stats') }}?</h3>
					<p>{{ config('app.name', 'Text Stats') }} analyzes text and outputs various, related statistics.  It provides information such as <a href="https://en.wikipedia.org/wiki/N-gram">n-grams</a>, <a href="https://en.wikipedia.org/wiki/Lexical_density">lexical density</a>, and word frequency, so writers can use the site to improve their own work and compare it to others. </p>
					<h3>Why?</h3>
					<p>Because I found that current freely available analysis websites are lacking in features, and text analysis is a field of computer science that interests me.</p>
					<h3>How?</h3>
					<p>Technology used: <ul><li>LAPP (Linux, Apache, Postgres, PHP)</li><li>DigitalOcean hosting,</li><li>Laravel PHP framework,</li><li>Bootstrap framework,</li><li>Google charts,</li></ul> 
					<p>See the Github page <a href="https://github.com/V296Q4/text_stats/">here</a>.</p>
					<p>Click 'Analyze' at the top of the page to get started, or 'Browse' to see texts analyzed by other users.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
