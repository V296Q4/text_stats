@extends('layouts.app')

@section('page_title') Text Stats - Upload @endsection

@section('main_content')
<div class="container">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">Text Stats</h2></div>

                <div class="panel-body">
                    
					<p>Paste into box.</p>

					{!! Form::open(['action' => 'UploadController@submitted']) !!}
					<div class="form-group">
					{!! Form::text('title', 'Document Name') !!}
					{!! Form::textarea('document', 'paste text here') !!}
					{!! Form::select('type', ['Essay' => 'essay', 'Fiction' => 'fiction', 'Other' => 'other']) !!}
					{!! Form::checkbox('is_private', 'false') !!}
					{!! Form::submit('submit') !!}
					</div>
					{!! Form::close() !!}
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
