<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redirect;
use Auth;

class DocumentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }
	
	public function random(){
		$random_document = DB::table('documents')->select('id')->where('is_private', 'false')->inRandomOrder()->first();
		if(isset($random_document->id)){
			return Redirect::to("/document/" . $random_document->id);
		}
		else{
			return Redirect::to('home')->with('message', '<p class="bg-warning">Document not found.</p>');
		}
	}

	
	public function guest_document(\App\Document $document){
		dd($document);
	}
	
    /**
     * Show the document.
     *
     */
    public function index($id)
    {
		$document_id = intval($id);
		$document = DB::table('documents as d')->join('users', 'users.id', '=', 'd.created_by')->select('d.id as id', 'd.title', 'd.version', 'd.type', 'd.is_private', 'd.text',  'd.word_count', 'd.unique_word_count', 'd.average_word_length',  'd.character_count', 'd.created_at', 'users.name as created_by', 'd.created_by as created_by_id', 'd.sentence_count', 'd.period_end_count', 'd.question_end_count', 'd.exclaim_end_count', 'd.longest_sentence_word_count', 'd.longest_sentence_content', 'd.shortest_sentence_word_count', 'd.shortest_sentence_content', 'd.average_sentence_word_length', 'd.average_sentence_character_length', 'd.sentence_lengths', 'd.word_lengths', 'd.commas_per_sentence', 'd.longest_word', 'd.description', 'd.dialogue_paragraph_count', 'd.non_dialogue_paragraph_count', 'd.average_paragraph_word_count', 'd.paragraph_lengths')->where('d.id', $document_id)->first();
		$view_variables = array();

		if(!isset($document)){
			return Redirect::to('home')->with('message', '<p class="bg-warning">Document not found.</p>');
		}
		
		//Private Document Handling
		if($document->is_private && $document->created_by_id !== Auth::id()){
			return Redirect::to('home')->with('message', '<p class="bg-warning">Document not found.</p>');
		}

		//Create primary string to display in blade
		$main_text = '<p>Document Type: ' . $document->type . '</p>';
		
		if($document->version !== '1'){
			$main_text .= '<p>Document Version: ' . $document->version . '</p>';
		}
		
		if($document->text !== null){
			$raw_url = '<h3 style="text-align:center"><a href="' . $document->id . '/raw">Raw</a></h3>';
		}
		else{
			$raw_url = '<h2 style="text-align:center">Raw Text Not Available</a></h2>';
		}
		
		if($document->is_private == true){
			$main_text .= '<p>Document is private (only you can see it).</p>';//TODO: show only if user is uploader - add buttons to toggle visibility
		}
		else{
			$main_text .= '<p>Document is public (anyone can see it).</p>';
		}
		$main_text .= "<p>Uploaded by '$document->created_by' on $document->created_at.</p>";
		
		if(strlen($document->description) > 0){
			$main_text .= '<b>Description:</b> ' . $document->description;
		}
		$main_text .= '<p title="Includes Spaces">Character Count: ' . $document->character_count . '</p>';

		/*
		 * Word Scope Statistics
		 *
		 */
		$word_scope_string = '<hr><h3>Word Scope Analysis:</h3>';
		$word_scope_string .= '<p>Word Count: ' . $document->word_count . '</p>';
		$percent_unique_words = round($document->unique_word_count * 100 / max($document->word_count, 1), 2);
		$word_scope_string .= "<p>Unique Word Count: $document->unique_word_count ($percent_unique_words%)</p>";
		$word_scope_string .= '<p>Average Word Length: ' . round($document->average_word_length, 2) . ' characters</p>';
		$word_scope_string .= "Longest Word <small>(" . strlen($document->longest_word) . " characters)</small>: <blockquote>" . filter_var($document->longest_word, FILTER_SANITIZE_STRING) . "</blockquote>";

		//Create Frequent Words table
		if($percent_unique_words != 100){
			$frequent_words = DB::table('document_word')->select('word', 'quantity')->where([['document_id', $document_id], ['category_id', 0]])->orderBy('quantity', 'desc')->get();
			$word_scope_string .= self::create_word_table($document->word_count, $frequent_words, 0, 'Word Frequencies', 'Top 50 words.');
		}
		
		//Create Stoplisted Frequent Words table
		$frequent_words_stoplist = DB::table('document_word')->select('word', 'quantity')->where([['document_id', $document_id], ['category_id', 1]])->orderBy('quantity', 'desc')->get();
		if(count($frequent_words_stoplist) > 0){
			$word_scope_string .= self::create_word_table($document->word_count, $frequent_words_stoplist, 1, 'Filtered Word Frequencies', 'Top 50 words, excluding the most common.  <a href="/stoplist">Stoplist here</a>.');
		}
		
		//Create Frequent Proper Nouns Table
		$proper_word_list = DB::table('document_word')->select('word', 'quantity')->where([['document_id', $document_id], ['category_id', 3]])->orderBy('quantity', 'desc')->get();
		if(count($proper_word_list) > 0){
			$word_scope_string .= self::create_word_table($document->word_count, $proper_word_list, 2, 'Proper Noun Frequencies', 'Top 50 detected proper nouns.  Experimental - not 100% accurate.');
		}
		
		//Word Lengths Chart
		if(strlen($document->word_lengths) > 0){
			$view_variables['word_lengths'] = $document->word_lengths;
		}
		
		/*
		 * Sentence Scope Statistics
		 *
		 */
		$sentence_scope_string = "<hr><h3>Sentence Scope Analysis:</h3>";
		
		$other_sentence_ending_count = $document->sentence_count - $document->period_end_count - $document->exclaim_end_count - $document->question_end_count;
		$sentence_scope_string .= '<table class="table table-responsive table-striped"><thead><tr><th>Sentence End Punctuation</th><th>Quantity</th><th>%</th></tr></thead><tbody><tr><td>Period</td><td>' . $document->period_end_count . '</td><td>' . self::fraction_to_percent($document->period_end_count, $document->sentence_count) . '</td></tr><tr><td>Interrogation Point</td><td>' . $document->question_end_count . '</td><td>' . self::fraction_to_percent($document->question_end_count, $document->sentence_count) . '</td></tr><tr><td>Exclamation Mark</td><td>' . $document->exclaim_end_count . '</td><td>' . self::fraction_to_percent($document->exclaim_end_count, $document->sentence_count) . '</td></tr><tr><td>Other</td><td>' . $other_sentence_ending_count . '</td><td>' . self::fraction_to_percent($other_sentence_ending_count, $document->sentence_count) . '</td></tr></tbody></table>';
		$sentence_scope_string .= '<p>Total Sentence Count: ' . $document->sentence_count . '</p>';
		$sentence_scope_string .= '<p>Average Sentence Length: ' . round($document->average_sentence_word_length, 2) . ' word' . self::handle_plural($document->average_sentence_word_length) . ' (' . round($document->average_sentence_character_length, 2) . ' character' . self::handle_plural($document->average_sentence_character_length) . ')</p>';
		$sentence_scope_string .= 'Shortest Sentence <small>(' . strlen($document->shortest_sentence_content) . ' character' . self::handle_plural(strlen($document->shortest_sentence_content)) . ', or ' . $document->shortest_sentence_word_count . ' word' . self::handle_plural($document->shortest_sentence_word_count) . ')</small>: <blockquote>' . filter_var($document->shortest_sentence_content, FILTER_SANITIZE_STRING) . '</blockquote>';
		$sentence_scope_string .= 'Longest Sentence <small>(' . strlen($document->longest_sentence_content) . ' character' . self::handle_plural($document->longest_sentence_content) . ', or ' . $document->longest_sentence_word_count . ' word' . self::handle_plural($document->longest_sentence_word_count) . ')</small>: <blockquote>' . filter_var($document->longest_sentence_content, FILTER_SANITIZE_STRING) . '</blockquote>';
		
		//Create Frequent Frontword Table
		$frontword_list = DB::table('document_frontword')->select('word', 'quantity')->where('document_id', $document_id)->orderBy('quantity', 'desc')->get();
		if(count($frontword_list) > 1){
			$sentence_scope_string .= self::create_word_table($document->sentence_count, $frontword_list, 3, 'Frontword Frequencies', 'Top 50 words that sentences <i>begin</i> with.');
		}			
		
		//Sentence Lengths Chart
		if(strlen($document->sentence_lengths) > 0){
			$view_variables['sentence_lengths'] = $document->sentence_lengths;
		}
		
		//Commas vs Sentence Lengths Chart
		if(strlen($document->commas_per_sentence) > 0){
			$view_variables['commas_per_sentence'] = $document->commas_per_sentence;
		}
		
		//Paragraph Lengths Chart
		if(strlen($document->paragraph_lengths) > 0){
			$view_variables['paragraph_lengths'] = $document->paragraph_lengths;
		}
				
		/*
		 * Paragraph Scope Statistics
		 *
		 */
		$paragraph_scope_string = '<hr><h3>Paragraph Scope Analysis:</h3>';
		$paragraph_scope_string .= '<p>Paragraph Count: ' . ($document->dialogue_paragraph_count + $document->non_dialogue_paragraph_count) . ' (' . round($document->dialogue_paragraph_count * 10000/ max($document->dialogue_paragraph_count + $document->non_dialogue_paragraph_count, 1))/100 . '% containing dialogue)</p>';
		$paragraph_scope_string .= '<p>Average Paragraph Length: ' . $document->average_paragraph_word_count . ' words</p>';
		
		/*
		 * Collections Sidebar
		 *
		 */
		$collection_options = null;
		$collections = DB::table('collections')->select('name', 'id')->where('created_by', Auth::id())->get();
		if(sizeof($collections) > 0){
			$collection_options = "";
			foreach($collections as $collection){
				$collection_options .= '<option value="' . $collection->id . '">' . $collection->name . '</option>';
			}
		}

		//Send off
		$view_variables += ['title' => $document->title, 'raw_url'=> $raw_url, 'main_text' => $main_text, 'collection_options' => $collection_options, 'document_id' => $document_id, 'paragraph_scope_string' => $paragraph_scope_string, 'sentence_scope_string' => $sentence_scope_string, 'word_scope_string' => $word_scope_string];
        return view('document', $view_variables);
    }
	
	/*
	 * Returns an 's' if $quantity is not 1.
	 *
	 */
	public function handle_plural($quantity){
		if($quantity == 1){
			return '';
		}
		return 's';
	}
	
	public function fraction_to_percent($numerator, $denominator){
		 return round($numerator / max($denominator, 1), 3)*100;
	}
	
	/*
	 * Returns an html table from the given word list.
	 *
	 */
	public function create_word_table($ratio_denominator, $word_list, $table_id, $table_title, $table_subtitle){
		$table = '<div class="panel panel-default"><div class="panel-heading"><a class="text-center" role="button" data-toggle="collapse" href="#collapseWords' . $table_id . '" aria-expanded="false" aria-controls="collapseWords' . $table_id . '">' . $table_title . ' <span class="caret"></span></a> <small>' . $table_subtitle . '</small></div><div class="collapse" id="collapseWords' . $table_id . '"><table class="table table-responsive table-striped table-condensed"><thead><tr><th>Rank</th><th>Word</th><th>Frequency</th></thead><tbody>';
		$rank = 1;
		foreach($word_list as $word){
			$table .= "<tr><td class='col-md-1'>$rank</td><td>" . filter_var($word->word, FILTER_SANITIZE_STRING) . "</td><td>$word->quantity (" . round($word->quantity / $ratio_denominator * 100, 2) . "%)</td></tr>";
			$rank++;
		}
		$table .= '</tbody></table></div></div>' . '<p></p>';
		return $table;
	}
	
    /**
     * List of browsable documents.
     *
     */
	public function browse_documents()
    {
		$document_list = DB::table('documents')->select('id', 'title', 'created_at', 'word_count')->where('is_private', 'false')->orderBy('created_at', 'desc')->get();

		$table = '<table class="table table-striped table-responsive"><thead><tr><th>Title</th><th>Date</th><th>Word Count</th></tr></thead><tbody>';
		foreach($document_list as $doc){
			if(strlen($doc->title) > 40){
				$doc->title = substr($doc->title, 0, 38) . '...';
			}
			$table .= '<tr><td><a href="/document/' . $doc->id . '">' . $doc->title . '</a></td><td>' . $doc->created_at . '</td><td>' . $doc->word_count . '</td></tr>';
		}
		$table .= '</tbody></table>';

        return view('browse', ['table' => $table]);
    }
	
    /**
     * List of documents uploaded by the user.
     *
     */
	public function browse_owned_documents(){
		$user_id = Auth::id();
		$document_list = DB::table('documents')->select('id', 'title', 'type', 'word_count', 'is_private')->where('created_by', $user_id)->orderBy('created_at', 'desc')->get();
		$document_count = sizeof($document_list);
		$table = 'You have ' . $document_count . ' document' . (($document_count == 1) ? '' : 's') . '.<br><table class="table table-striped table-responsive"><thead><tr><th>Title</th><th>Type</th><th>Private</th><th>Word Count</th><th></th></tr></thead><tbody>';
		foreach($document_list as $doc){
			$is_private = ($doc->is_private ? 'Yes' : 'No');
			$table .= '<tr><td><a href="/document/' . $doc->id . '">' . $doc->title . '</a></td><td>' . $doc->type . '</td><td class="col-md-1">' . $is_private . '</td><td class="col-md-2">' . $doc->word_count . '</td><td class="col-md-1"><a href="/delete_document/' . $doc->id . '" type="button" class="btn btn-danger btn-sm">Delete</button></td></tr>';
		}
		$table .= '</tbody></table>';

        return view('owned_documents', ['table' => $table]);
	}
	
	/*
     * Deletes a document if authorized
     *
     */
	public function delete_document($id){
		$user_id = Auth::id();
		$document_id = intval($id);
		$document = DB::table('documents')->select('title', 'created_by')->where('id', $document_id)->first();
		
		if(!isset($document)){
			$message = 'Document does not exist.';
		}
		else{
			if($user_id === $document->created_by){
				DB::table('documents')->where('id', $document_id)->delete();
				$message = "Deleted document '$document->title' (ID: $document_id)";
			}
			else{
				$message = 'You have insufficient permissions.';
			}
		}
		
		return Redirect::to('home')->with('message', '<p class="bg-success">Deleted document "' . $document->title . '".</p>');
	}
	
    /**
     * Show the document's raw text.
     *
     */
	public function viewRaw($id){
		$user_id = Auth::id();
		$document_id = intval($id);
		$document = DB::table('documents')->select('id', 'text', 'created_by', 'is_private')->where('id', $document_id)->first();
		if(isset($document)){
			if($user_id === $document->created_by || !$document->is_private){
				$return_url = '/document/' . $document->id;
				return view('raw_document', ['main_text' => $document->text, 'return_url'=> $return_url]);
			}
		}
		return Redirect::to('home')->with('message', '<p class="bg-warning">Document not found.</p>');
	}
	
}
