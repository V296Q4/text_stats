@extends('layouts.app')

@section('page_title') Text Stats - Document @endsection

@section('main_content')
<div class="container">

	@if(session()->has('message'))
		{!! session()->get('message') !!}
	@endif

    <div class="row">
        <div class="col-md-8 ">
            <div class="panel panel-default">
                <div class="panel-heading"><h2 style="text-align:center">{!! $title !!}</h2>{!! $raw_url !!}</div>

                <div class="panel-body">
                    
					{!! $main_text !!}
					
					{!! $word_scope_string !!}
					
					@if(isset($word_lengths))
					<div id="word_lengths_chart_div"></div>
					@endif
					
					{!! $sentence_scope_string !!}
					
					@if(isset($sentence_lengths))
					<div id="sentence_lengths_chart_div"></div>
					@endif
					
					@if(isset($commas_per_sentence))
					<div id="commas_per_sentence_chart_div"></div>
					@endif
					
					{!! $paragraph_scope_string !!}
					
					@if(isset($paragraph_lengths))
					<div id="paragraph_lengths_chart_div"></div>
					@endif

					
                </div>
			
            </div>
			
        </div>
		
		<div class="pull-right col-md-4">
			<div class="panel panel-default">
				<div class="panel-heading"><h3 style="text-align:center">Add to Collection</h3></div>
				<div class="panel-body">
					@if(isset($collection_options))
					<form class="" role="form" method="POST" action="add_to_collection">
					
                        {{ csrf_field() }}
						
						<input class="hidden" name="document_id" value="{{$document_id}}">
						
						<div class="form-group">
							<label for="type" class="col-md-4 control-label">Collection:</label>
							<div class="col-md-8">
								<select class="form-control" id="type" name="collection_id">
								{!! $collection_options !!}
								</select>
							</div>
						
						</div>
						
						<div class="control-group">&nbsp;</div>
						
						<div class="form-group">
                            <div class="col-md-12">
                                <textarea id="comment" class="form-control" name="comment" rows="3" placeholder="comment">{{ old('comment') }}</textarea>
                            </div>
                        </div>
						
						<div class="control-group">&nbsp;</div>
						
						<div class="form-group">
                            <div class="col-md-6 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    Add
                                </button>
                            </div>
                        </div>
					</form>
					@elseif(null != Auth::id())
						<p>You have no collections.  Create one <a href="/create_collection">here</a>.</p>
					@else
						<p>You must be logged in to use this feature.</p>
					
					@endif
				</div>
			</div>
		</div>	
		
    </div>

	
</div>
@endsection

@section('scripts')
@if(isset($sentence_lengths) || isset($word_lengths))
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
<script type="text/javascript">
    google.charts.load('current', {'packages':['corechart', 'line', 'bar']});

	@if(isset($sentence_lengths))
	google.charts.setOnLoadCallback(drawSentenceLengthsChart);
    function drawSentenceLengthsChart() {
		var sentence_lengths = {!! $sentence_lengths !!};
		var data = new google.visualization.arrayToDataTable(sentence_lengths);
		var chart = new google.visualization.ColumnChart(document.getElementById('sentence_lengths_chart_div'));
		var options = {'title': 'Sentence Lengths', 'legend':'none'};
		chart.draw(data, options);
    }
	@endif
	
	@if(isset($commas_per_sentence))
	google.charts.setOnLoadCallback(drawCommasPerSentenceChart);
    function drawCommasPerSentenceChart() {
		var commas_per_sentence = {!! $commas_per_sentence !!};
		var data = new google.visualization.arrayToDataTable(commas_per_sentence);
		var chart = new google.visualization.LineChart(document.getElementById('commas_per_sentence_chart_div'));
		var options = {'title': 'Commas VS Sentence Lengths', 'legend':'none'};
		chart.draw(data, options);
    }
	@endif
	
	@if(isset($word_lengths))
	google.charts.setOnLoadCallback(drawWordLengthsChart);
	function drawWordLengthsChart(){
		var word_lengths = {!! $word_lengths !!};
		var data = new google.visualization.arrayToDataTable(word_lengths);
		var chart = new google.visualization.ColumnChart(document.getElementById('word_lengths_chart_div'));
		var options = {'title': 'Word Lengths', 'legend':'none'};
		chart.draw(data, options);
	}
	@endif	
	
	@if(isset($paragraph_lengths))
	google.charts.setOnLoadCallback(drawParagraphLengthsChart);
    function drawParagraphLengthsChart() {
		var paragraph_lengths = {!! $paragraph_lengths !!};
		var data = new google.visualization.arrayToDataTable(paragraph_lengths);
		var chart = new google.visualization.ColumnChart(document.getElementById('paragraph_lengths_chart_div'));
		var options = {'title': 'Paragraph Lengths', 'legend':'none'};
		chart.draw(data, options);
    }
	@endif
</script>
@endif

@endsection
