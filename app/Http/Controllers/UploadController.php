<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Input;
use Redirect;
use App;

class Word{
	protected $content = '';
	protected $is_in_dialogue = false;
	
	public function __construct($text, $dialogue){
		$this->content = $text;
		$this->is_in_dialogue = $dialogue;
	}
	
	public function get_content(){
		return $this->content;
	}
	
	public function get_is_in_dialogue(){
		return $this->is_in_dialogue;
	}
	
}

class Sentence{
	protected $text = '';//TODO need or not? we lose all punctuation without it
	protected $has_dialogue = false;
	protected $end_punctuation = '';
	protected $word_count = 0;
	protected $word_list = array();
	protected $ends_in_dialogue = false;
	
	public function __construct($text, $continuing_dialogue = false){
		$this->text = $text;
		//if the text ends with a quote, use second to last character instead
		$last_char = substr($text, -1);
		if($last_char == '"' || $last_char == '\''){
			$last_char = substr($text, -2, 1);
		}
		
		$this->end_punctuation = $last_char;//usually . ! ? -
		$sentence_chunks = preg_split('/(?<=[\s,;.!?()"–])/', $text);
		array_walk($sentence_chunks, 'self::trim_value');
		array_walk($sentence_chunks, 'self::convert_string_mb');
		
		$remove = array(',','.','?','!');
		$sentence_chunks = str_replace($remove, '', $sentence_chunks);//remove unwanted characters
		//$sentence_chunks = array_filter($sentence_chunks, function($value) { return $value !== '' && $value !== '?';});//TODO make remove empties function // && $value !== '"' && $value !== '-'
		$sentence_chunks = array_filter($sentence_chunks, 'strlen');//TODO make remove empties function // && $value !== '"' && $value !== '-'
		
		$is_in_dialogue = $continuing_dialogue;
		foreach($sentence_chunks as $word){
			if(strpos($word, '"') !== false){
				$is_in_dialogue = !$is_in_dialogue;
				$this->has_dialogue = true;
			}
			$word_in_dialogue = $is_in_dialogue;
			
			if(strpos($word, '"') === strlen($word) - 1){
				$word_in_dialogue = true;
			}
			
			$this->word_list[] = new Word($word, $word_in_dialogue);

		}
		
		$this->ends_in_dialogue = $is_in_dialogue;
		$this->word_list = array_filter($this->word_list, function($value){return $value !== '';});
		
		$this->word_count = count($this->word_list);
	}
	
	function trim_value(&$value){
		$value = trim($value);
	}	
	
	public function convert_string_mb(&$value){
		$value = mb_convert_encoding($value , 'UTF-8' , 'UTF-8');
	}

	public function get_word_list(){
		return $this->word_list;
	}
	
	public function get_text(){
		return $this->text;
	}
	
	public function get_end_punctuation(){
		return $this->end_punctuation;
	}
	
	public function get_ends_in_dialogue(){
		return $this->ends_in_dialogue;
	}
	
}

class Paragraph{
	protected $sentence_list = array();
	protected $has_dialogue = false;
	
	function trim_value(&$value){
		$value = trim($value);
	}	

	public function convert_string_mb(&$value){
		$value = mb_convert_encoding($value , 'UTF-8' , 'UTF-8');
	}
	
	public function get_sentence_list(){
		return $this->sentence_list;
	}
	
	public function get_has_dialogue(){
		return $this->has_dialogue;
	}
		
	public function __construct($text){
		//note \p{Lu} in the following line matches any uppercase letter that has a lowercase variant, \p{L} is any letter in any language
		//$sentence_content_list = preg_split('/(?<=[”.?!\r\n]|-)(?<![\s"“\']\p{Lu}.|\s\p{Lu}[a-z].)(?=[\s\'"”“]+\p{Lu}|"|\d|“)(?![\'”"])/', $text);
		//reminder that smartquotes were converted to standard quotes already
		$sentence_content_list = preg_split('/(?<=[.?!\r\n]|-)(?<![\s"\']\p{Lu}.|\s\p{Lu}[a-z].|[,?!]")(?=[\s\'"]+\p{Lu}|"|\d)(?![\'"])/', $text);
		array_walk($sentence_content_list, 'self::trim_value');
		array_walk($sentence_content_list, 'self::convert_string_mb');
		$sentence_content_list = array_filter($sentence_content_list, function($value) { return $value !== '' && $value !== '?';});

		if(strpos($text, '"') !== false){
			$this->has_dialogue = true;
		}
		
		$continuing_dialogue = false;
		foreach($sentence_content_list as $s){
			$this->sentence_list[] = new Sentence($s, $continuing_dialogue);
			$continuing_dialogue = end($this->sentence_list)->get_ends_in_dialogue();
		}
	}
}

class UploadController extends Controller
{
	public $paragraph_objects = array();
			
	public $raw_word_list = array();
	public $sentence_frontwords = array();
	public $sentence_non_frontwords = array();
		
	public $sentence_length_vs_commas = array();
	public $sentence_lengths = array();
	public $word_lengths = array();
	public $total_characters = 0;
	
	public $repeated_frontwords = array();
	public $previous_frontword = null;
	
	public $period_end_count = 0;
	public $period_end_count_in_dialogue = 0;
	public $question_end_count = 0;
	public $question_end_count_in_dialogue = 0;
	public $exclaim_end_count = 0;
	public $exclaim_end_count_in_dialogue = 0;
	public $dash_end_count = 0;
	public $dash_end_count_in_dialogue = 0;
	public $other_end_count = 0;
	public $other_end_count_in_dialogue = 0;
		
	public $longest_sentence_word_count = -1;	
	public $shortest_sentence_word_count = -1;
	
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }
	
	/**
	 * Returns the upload text view.
	 *
	 */
	public function index(){
		return view('upload', ['character_limit' => self::get_character_limit()]);
	}

	/*
	 * Redirects the user back with an error message.
	 *
	 */
	public function stop_on_error($error){
		return back()->with('message', '<p class="bg-danger">Error: ' . $error . '</p>');
	}

	/*
	 * Returns character limit for the user's upload
	 *
	 */	
	public function get_character_limit(){
		return Auth::check() ? 2000000 : 1000000;
	}
	
	public function clean_string($string, $length){
		if($length === -1){
			return mb_convert_encoding(filter_var(trim($string), FILTER_SANITIZE_STRING), "UTF-8");
		}
		else{
			return mb_convert_encoding(substr(filter_var(trim($string), FILTER_SANITIZE_STRING),0,$length), "UTF-8");
		}
	}

	/*
	 * Trims whitespace from given string
	 *
	 */
	function trim_value(&$value){
		$value = trim($value);
	}	

	public function convert_string(&$value){
		$value = iconv("UTF-8", "UTF-8//IGNORE", $value);
	}
	
	public function convert_string_mb(&$value){
		$value = mb_convert_encoding($value , 'UTF-8' , 'UTF-8');
	}
	
	/*
	 * Returns true if given string is in postgres stoplist
	 *
	 */
	public function is_in_stoplist($word){
		$stoplist = config('stoplist.postgres_stoplist');
		return in_array(strtolower($word), $stoplist);
	}
	
	public function get_word_list($word_object){
		return $word_object->get_word_list();
	}
	
	public function handle_sentence($sentence){
		$word_object_list = $sentence->get_word_list();
		
		$sentence_word_list = array();
		foreach($sentence->get_word_list() as $word_object){
			$sentence_word_list [] = $word_object->get_content();
		}
		
		
		if(count($sentence_word_list ) > 0){
			array_push($this->raw_word_list, ...$sentence_word_list );
		}
		
		if($sentence->get_end_punctuation() === '.'){
			$this->period_end_count ++;
			if($sentence->get_ends_in_dialogue()){
				$this->period_end_count_in_dialogue ++;
			}
		}
		elseif($sentence->get_end_punctuation() === '?'){
			$this->question_end_count ++;
			if($sentence->get_ends_in_dialogue()){
				$this->question_end_count_in_dialogue ++;
			}
		}
		elseif($sentence->get_end_punctuation() === '!'){
			$this->exclaim_end_count ++;
			if($sentence->get_ends_in_dialogue()){
				$this->exclaim_end_count_in_dialogue ++;
			}
		}
		elseif($sentence->get_end_punctuation() === '-' || $sentence->get_end_punctuation() === '—'){//en or em dash
			$this->dash_end_count ++;
			if($sentence->get_ends_in_dialogue()){
				$this->dash_end_count_in_dialogue ++;
			}
		}
		else{
			$this->other_end_count ++;
			if($sentence->get_ends_in_dialogue()){
				$this->other_end_count_in_dialogue ++;
			}
			
			//TODO
			//dd($sentence->get_end_punctuation());
		}
		
		//adding to frontwords frequency list
		if(count($word_object_list) >= 1){
			$current_frontword = reset($word_object_list);
			$current_frontword_content = $current_frontword->get_content();
			
			//repeated frontword
			if(isset($this->previous_frontword) && $current_frontword_content == $this->previous_frontword->get_content()){
				if(!$current_frontword->get_is_in_dialogue() && !$this->previous_frontword->get_is_in_dialogue()){
					$this->repeated_frontwords[] = $current_frontword_content;//exclude if in dialogue
				}
			}
			$this->previous_frontword = $current_frontword;
			
			if(isset($this->sentence_frontwords[$current_frontword_content])){
				$this->sentence_frontwords[$current_frontword_content] ++;
			}
			else{
				$this->sentence_frontwords[$current_frontword_content] = 1;
			}

		}
		
		//adding to non frontwords frequency list
		if(count($sentence_word_list) >= 2){
			foreach($sentence_word_list as $word){
				if($word !== reset($sentence_word_list)){
					if(isset($this->sentence_non_frontwords[$word])){
						$this->sentence_non_frontwords[$word] ++;
					}
					else{
						$this->sentence_non_frontwords[$word] = 1;
					}
				}
			}
		}
		
		//adding to sentence_lengths list
		if(!isset($this->sentence_lengths[count($sentence_word_list)])){
			$this->sentence_lengths[count($sentence_word_list)] = 1;
		}
		else{
			$this->sentence_lengths[count($sentence_word_list)]++;
		}
		
		//comma vs sentence length graph data
		//TODO do this smarter
		$commas_in_sentence = substr_count($sentence->get_text(), ',');
		if(isset($this->sentence_length_vs_commas[count($sentence_word_list)])){
			$this->sentence_length_vs_commas[count($sentence_word_list)] += $commas_in_sentence;
		}
		else{
			$this->sentence_length_vs_commas[count($sentence_word_list)] = $commas_in_sentence;
		}
		
		//building sentence lengths stats
		if(!isset($this->longest_sentence_content) || strlen($sentence->get_text()) > strlen($this->longest_sentence_content)){
			$this->longest_sentence_word_count = count($sentence_word_list);
			$this->longest_sentence_content = $sentence->get_text();
		}
		if(!isset($this->shortest_sentence_content) || strlen($sentence->get_text()) < strlen($this->shortest_sentence_content)){
			$this->shortest_sentence_word_count = count($sentence_word_list);
			$this->shortest_sentence_content = $sentence->get_text();
		}
		
		$this->total_characters += strlen($sentence->get_text());
	}
	
	//TODO merge into histograms function?
	public function get_word_lengths_chart_data($paragraphs){
		$word_lengths = array();
		foreach($paragraphs as $paragraph){
			foreach($paragraph->get_sentence_list() as $sentence){
				foreach($sentence->get_word_list() as $word_object){
					$w = $word_object->get_content();
					if(isset($word_lengths[strlen($w)])){
						$word_lengths[strlen($w)] ++;
					}
					else{
						$word_lengths[strlen($w)] = 1;
					}
				}
			}
		}
		
		$result = '';
		if(count($word_lengths) > 0){
			$result = '[["Word Length", "Quantity"],';
			foreach($word_lengths as $length => $quantity){
				$result .= '[' . $length . ', ' . $quantity . '],';
			}
			$result = substr($result, 0, strlen($result) - 1) . ']';
		}
		
		return $result;
	}
	
	public function histogram_string_from_array($array, $title_text, $bucket_size = 2){
		$resulting_string = "";
		if(count($array) > 1){
			$resulting_string = '[["' . $title_text . '", "Quantity"],';
			$currentBucket = $bucket_size;
			$bucketContents = 0;
			ksort($array);
			foreach($array as $length => $quantity){
				if($length <= $currentBucket){
					$bucketContents += $quantity;
				}
				else{//move to next bucket size
					$resulting_string .= '[' . $currentBucket . ', ' . $bucketContents . '],';
					$bucketContents = $quantity;
					$currentBucket += $bucket_size;
				}
			}
			$resulting_string .= '[' . $currentBucket . ', ' . $bucketContents . '],';
			$resulting_string = substr($resulting_string, 0, strlen($resulting_string)-1) . ']';
		}
		return $resulting_string;
	}
	
	
    /**
     * Handle posted (submitted) documents.
     *
     */
    public function submitted(Request $request)
    {
		$start = microtime(true);
		$document = new App\Document;

		//Basic info:
		$document->title = self::clean_string(Input::get('title'), 128);
		$document->description = self::clean_string(Input::get('description'), 2048);
		
		mb_language('uni');
		mb_internal_encoding('UTF-8');
		//mb_substitute_character(0xFFFD);

		$text = trim(Input::get('text'));
		self::convert_string($text);
		
		//replace smart quotes with standard quotes
		$smart_quotes = array('”','“');
		$text = str_replace($smart_quotes, '"', $text);
		
		$document->text = $text;
		$document->character_count = strlen($document->text);
		#$document->type = substr(filter_var(trim(Input::get('type')), FILTER_SANITIZE_STRING),0,64);
		$document->type = self::clean_string(Input::get('type'), 64);
		$document->is_private = Input::get('is_private') ? true : false;
		$document->created_by = (Auth::Check()) ? intval(Auth::user()->id) : -1;
		$document->created_at = date('Y-m-d H:i:s');//use current date
		$document->sentence_count = 0;
		
		if(strlen($document->description) === 0){
			$document->description = '';
		}
		if(strlen($document->title) === 0){
			$document->title = $document->created_at;
		}
		
		if(strlen($document->title) < 3){
			//return self::stop_on_error('Title must be at least 3 characters long.');//TODO but if guest?
		}
		if($document->character_count < 2){
			return self::stop_on_error('Text must be at least 2 characters long.');
		}
		if($document->character_count > self::get_character_limit()){//TODO or just auto cut to limit?
			return self::stop_on_error("Text is $document->character_count characters long.  The limit is $character_limit");
		}
		
		/*
		 * Paragraph-scope processing:
		 * 
		 */
		$all_paragraphs = preg_split('/(?<=[\r\n])/', $document->text);
		array_walk($all_paragraphs, 'self::trim_value');
		$all_paragraphs = array_filter($all_paragraphs, function($value) { return $value !== ''; });//remove empty paragraphs

		$document->dialogue_paragraph_count = 0;
		$document->non_dialogue_paragraph_count = 0;
		
		foreach($all_paragraphs as $paragraph){
			$paragraph_objects[] = new Paragraph($paragraph);
		}
		unset($all_paragraphs);
		
		/*
		 * Sentence-scope count processing:
		 *
		 */
		$sentence_counts_in_paragraphs = array();

		foreach($paragraph_objects as $paragraph){
			$sentence_count = count($paragraph->get_sentence_list());
			
			if($paragraph->get_has_dialogue()){
				$document->dialogue_paragraph_count ++;
			}
			else{
				$document->non_dialogue_paragraph_count ++;
			}
			
			if(isset($sentence_counts_in_paragraphs[$sentence_count])){
				$sentence_counts_in_paragraphs[$sentence_count] ++;
			}
			else{
				$sentence_counts_in_paragraphs[$sentence_count] = 1;
			}
			
			$document->sentence_count += $sentence_count;
			
			if(count($paragraph->get_sentence_list()) > 0){
				foreach($paragraph->get_sentence_list() as $sentence){
					self::handle_sentence($sentence);
				}
			}
			
		}
		
		$document->period_end_count = $this->period_end_count;
		$document->period_end_count_in_dialogue = $this->period_end_count_in_dialogue;
		$document->question_end_count = $this->question_end_count;
		$document->question_end_count_in_dialogue = $this->question_end_count_in_dialogue;
		$document->exclaim_end_count = $this->exclaim_end_count;
		$document->exclaim_end_count_in_dialogue = $this->exclaim_end_count_in_dialogue;
		$document->dash_end_count = $this->dash_end_count;
		$document->dash_end_count_in_dialogue = $this->dash_end_count_in_dialogue;
		
		$document->longest_sentence_word_count = $this->longest_sentence_word_count;
		$document->longest_sentence_content = $this->longest_sentence_content;	
		$document->shortest_sentence_word_count = $this->shortest_sentence_word_count;
		$document->shortest_sentence_content = $this->shortest_sentence_content;
		
		$document->average_sentence_character_length = $this->total_characters / max(1, $document->sentence_count);
		$document->average_sentence_word_length = count($this->raw_word_list) / max(1, $document->sentence_count);			
		
		//Create comma vs sentence string for chart
		ksort($this->sentence_length_vs_commas);
		if(count($this->sentence_length_vs_commas) <= 1){
			$document->commas_per_sentence = '';
		}
		else{
			$document->commas_per_sentence = '[["Sentence Length", "Commas"],';
			foreach($this->sentence_length_vs_commas as $length => $comma_count){
				$comma_count /= max(1, $this->sentence_lengths[$length]);
				$document->commas_per_sentence .= '[' . $length . ', ' . $comma_count . '],';
			}
			$document->commas_per_sentence = substr($document->commas_per_sentence, 0, strlen($document->commas_per_sentence) - 1) . ']';
		
		}
		
		//Create word_lengths_chart_data string
		$document->word_lengths_chart = self::get_word_lengths_chart_data($paragraph_objects);
		
		//Create sentence_lengths string for chart
		$document->sentence_lengths_chart = self::histogram_string_from_array($this->sentence_lengths, 'Sentence Lengths (in words)');
		
		//Create paragraph_lengths string for chart
		$document->paragraph_lengths_chart = self::histogram_string_from_array($sentence_counts_in_paragraphs, 'Paragraph Length (in sentences)', 1);

		/*
		 * Word-scope processing:
		 * 
		 */
		$word_frequency_list = array();
		$word_list_postgres_stoplist = array();
		$document->word_count = 0;
		$document->unique_word_count = 0;
		$document->average_word_length = 0;
		$document->longest_word = '';
		if(count($this->raw_word_list) > 0){
			//Create word frequency list
			$total_chars = 0;
			foreach($this->raw_word_list as $word){
				$word = trim($word);
				if(strlen($word) > 0 && mb_check_encoding($word, "UTF-8")){
					if(strlen($word) > strlen($document->longest_word)){
						$document->longest_word = $word;
					}
					$word = strtolower($word);
					$total_chars += strlen($word);
					if(array_key_exists($word, $word_frequency_list)){
						$word_frequency_list[$word] += 1;
					}
					else{
						$word_frequency_list[$word] = 1;
					}
					$document->word_count++;
				}
			}
			
			$document->unique_word_count = count($word_frequency_list);
			$document->average_word_length = $total_chars / max($document->word_count, 1);
			arsort($word_frequency_list);
			
			//create a postgres stoplist version of the list
			foreach($word_frequency_list as $word => $frequency){
				if(!self::is_in_stoplist($word)){
					$word_list_postgres_stoplist[$word] = $frequency;
				}
			}
			$word_list_postgres_stoplist = array_slice($word_list_postgres_stoplist, 0, 50, true);
			
			$word_frequency_list = array_slice($word_frequency_list, 0, 50, true);
			
		}
		
		$proper_names_list = array();
		if(count($this->sentence_non_frontwords) > 0){
			//Create proper nouns list
			//TODO: how to filter out start of dialogue following an opening tag, IE [He said, "Sure."]
			
			foreach($this->sentence_non_frontwords as $word => $frequency){
				if(!self::is_in_stoplist($word)){
					//Check if word is capitalized
					$char = $word[0];
					if($char >= 'A' && $char <= 'Z' && strlen($word) >= 2 && $word[1] !== "'"){
						if(strpos($word, '\'s') !== false){
							$word = substr($word, 0, strpos($word, '\'s'));
						}
						
						if(isset($proper_names_list[$word])){
							$proper_names_list[$word] += $frequency;
						}
						else{
							$proper_names_list[$word] = $frequency;
						}
					}
				}
			}
			foreach($this->sentence_frontwords as $word => $frequency){
				if(isset($proper_names_list[$word])){
					$proper_names_list[$word] += $frequency;
				}
			}
			arsort($proper_names_list);
			$proper_names_list = array_slice($proper_names_list, 0, 50, true);

			//Sorted top frontwords array
			arsort($this->sentence_frontwords);
			$this->sentence_frontwords = array_slice($this->sentence_frontwords, 0, 50, true);
			
		}
		
		$document->average_paragraph_word_count = round($document->word_count / ($document->non_dialogue_paragraph_count + $document->dialogue_paragraph_count) * 100) / 100;
		
		$sentence_frontwords = $this->sentence_frontwords;
		
		// dd($this->repeated_frontwords);
		//dd($this->sentence_frontwords);
		
		$temps = '';
		foreach($paragraph_objects as $paragraph){
			if(count($paragraph->get_sentence_list()) > 0){
				foreach($paragraph->get_sentence_list() as $sentence){
					foreach($sentence->get_word_list() as $word){
						if($word->get_is_in_dialogue()){
							$temps .= $word->get_content() . " ";
						}
						else{
							//$temps .= $word->get_content() . "( ) /";
						}
					}
				}
				$temps .= "\n";
			}
		}
		dd($temps);
		
		
		$document->run_time = '' . (microtime(true) - $start);	
		
		/*
		 *  db category ids:
		 *	0 - word
		 *	1 - light stoplist
		 *  2 - heavy stoplist
		 *  3 - proper names
		 *
		 */
		$new_document_id = null;
		DB::transaction(function() use (&$new_document_id, $document, $word_frequency_list, $word_list_postgres_stoplist, $sentence_frontwords, $proper_names_list){
			//Insert document
			$new_document_id = DB::table('documents')->insertGetId($document['attributes']);
			
			//Prepare the word data
			$insertable_word_frequencies = array();
			$insertable_stoplisted_word_frequencies = array();
			$insertable_frontword_frequencies = array();
			$insertable_proper_frequencies = array();
			foreach($word_frequency_list as $word => $frequency){
				$insertable_word_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency, 'category_id' => 0];
			}	

			foreach($word_list_postgres_stoplist as $word => $frequency){
				$insertable_stoplisted_word_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency, 'category_id' => 1];
			}				
			
			foreach($sentence_frontwords as $word => $frequency){
				$insertable_frontword_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency];
			}				
			
			foreach($proper_names_list as $word => $frequency){
				$insertable_proper_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency, 'category_id' => 3];
			}		
			
			//Insert word data
			DB::table('document_word')->insert($insertable_word_frequencies);
			DB::table('document_word')->insert($insertable_stoplisted_word_frequencies);
			DB::table('document_frontword')->insert($insertable_frontword_frequencies);	
			DB::table('document_word')->insert($insertable_proper_frequencies);	
			
			//Update statistics
			DB::table('global_stats')->where('stat','documents_analyzed')->increment('value');
			DB::table('global_stats')->where('stat','words_analyzed')->increment('value', $document->word_count);
		});
		
		//Redirect
		if(!isset($new_document_id)){
			return Redirect::to('home')->with('message','<p class="bg-danger">Error.</p>');
		}
		return Redirect::to("/document/$new_document_id")->with('message', '<p class="bg-success">Document uploaded successfully.</p>');
			
    }

	
}
