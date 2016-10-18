@extends('layouts.app')

@section('page_title') Text Stats - Home @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">Text Stats</h2></div>

                <div class="panel-body">
                    
					{{ $message or '' }}
					
					<p>something about quantity analyzed (# texts, # words, average word count per text) & average world length per text</p>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection