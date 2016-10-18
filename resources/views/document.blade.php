@extends('layouts.app')

@section('page_title') Text Stats - Document @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">{!! $title !!}</h2><h3 style="text-align:center"><a href="">Raw</a></h3></div>

                <div class="panel-body">
                    
					{!! $main_text !!}
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
