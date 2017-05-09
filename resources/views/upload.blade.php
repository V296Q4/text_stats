@extends('layouts.app')

@section('page_title') Text Stats - Analyze @endsection

@section('main_content')
<div class="container">

	@if(session()->has('message'))
		{!! session()->get('message') !!}
	@endif

    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">Analyze Text</h2></div>

                <div class="panel-body">

					<form class="form-horizontal" role="form" method="POST" action="analyze_submitted">
                        {{ csrf_field() }}

						<p for="text" class="text-center"><b>Text</b></p>
                        <div class="form-group">
							<div class="col-md-2"></div>
                            <div class="col-md-8">
                                <textarea id="text" class="form-control" name="text" placeholder="{{ $character_limit }} character limit." rows="8" required autofocus>{{ old('text') }}</textarea>
                            </div>
                        </div>
						
						<hr>
						
						@if(Auth::Check())
							
						<div class="form-group">
							<div class="checkbox col-md-7 control-label">
									<label>
										<input type="checkbox" id="save_document" name="save_document">Save the Text
									</label>
							</div>
						</div>
						
						<p for="title" class="text-center"><b>Title</b></p>
						<div class="form-group">
							<div class="col-md-3"></div>
							<div class="col-md-6">
								<input id="title" type="text" class="form-control" name="title" value="{{ old('title') }}" placeholder="Minimum 3 characters.">
                            </div>
                        </div>
						
						<p for="description" class="text-center"><b>Description</b></p>
                        <div class="form-group">
							<div class="col-md-3"></div>
                            <div class="col-md-6">
                                <textarea id="description" class="form-control" name="description" placeholder="Optional.  Year?  Author?  Text type?" rows="2">{{ old('description') }}</textarea>
                            </div>
                        </div>
						
						<div class="form-group">
							<label for="type" class="col-md-4 control-label">Select Document Type:</label>
							<div class="col-md-6">
								<select class="form-control" id="type" name="type">
									<option value="unspecified">Unspecified</option>
									<option value="essay">Essay</option>
									<option value="fiction (chapter)">Fiction (chapter)</option>
									<option value="other">Other</option>
								</select>
							</div>
						</div>

						
						<div class="form-group">
							<div class="checkbox col-md-6 control-label">
									<label>
										<input type="checkbox" id="is_private" name="is_private" checked>Text is Private
									</label>
							</div>
						</div>
						
						<div class="form-group">
							<label for="add_to_collection" class="col-md-4 control-label">Add to Collection:</label>
							<div class="col-md-6">
								<select class="form-control" id="add_to_collection" name="add_to_collection">
									<option value="-1">None</option>
									
								</select>
							</div>
						</div>
						@else
						<div class="text-center">
							<p>Login for additional features:
							<ul><li>2 million character limit</li><li>Saving document statistics</li><li>Creating collections</li><li>Comparing documents</li><li>Fiction analysis (dialogue, sentence variability)</li></ul>
							</p>
						</div>
						@endif
						
						<hr>
						
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Analyze
                                </button>
                            </div>
                        </div>
                    </form>
					
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!--
<script src="//cdn.tinymce.com/4/tinymce.min.js"></script>
<script>tinymce.init({ selector:'textarea' });</script>
-->
@endsection
