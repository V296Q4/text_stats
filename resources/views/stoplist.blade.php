
@if(null !== config('stoplist.postgres_stoplist'))
	@foreach(config('stoplist.postgres_stoplist') as $item)
		{{ $item }}
	@endforeach
@else
	error: stoplist not found
@endif