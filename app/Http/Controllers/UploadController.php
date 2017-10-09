<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Input;
use Redirect;
use App;

class UploadController extends Controller
{
	
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
	public function trim_value(&$value){
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
	
    /**
     * Handle posted (submitted) documents.
     *
     */
    public function submitted(Request $request)
    {
		$document = new App\Document;

		//Basic info:
		$document->title = self::clean_string(Input::get('title'), 128);
		$document->description = self::clean_string(Input::get('description'), 2048);
		
		mb_language('uni');
		mb_internal_encoding('UTF-8');
		//mb_substitute_character(0xFFFD);
		
		// $document->text = mb_convert_encoding(trim(Input::get('text')), 'UTF-8', 'UTF-8');
		
		// $text = trim(Input::get('text'));
		$text = Input::get('text');
		self::convert_string($text);
		$document->text = $text;
		
		//$document->text = htmlspecialchars_decode(htmlspecialchars($document->text, ENT_SUBSTITUTE, 'UTF-8'));
		//$document->text = mb_convert_encoding(trim(Input::get('text')), "UTF-8");
		//$document->text = trim(Input::get('text'));
		// $document->text = utf8_decode(trim(Input::get('text')));
		// dd(mb_detect_encoding($document->text);
		
		
		// $document->text = iconv('UTF-8', 'UTF-8//TRANSLIT', trim(Input::get('text')));
		
		
		$document->character_count = strlen($document->text);
		$document->type = substr(filter_var(trim(Input::get('type')), FILTER_SANITIZE_STRING),0,64);
		$document->is_private = Input::get('is_private') ? true : false;
		$document->created_by = (Auth::Check()) ? intval(Auth::user()->id) : -1;
		$document->created_at = date('Y-m-d H:i:s');//use current date
		
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
		
		$raw_word_list = array();
		$sentence_frontwords = array();
		$sentence_non_frontwords = array();
		$sentence_list = array();
		
		/*
		 * Paragraph-scope processing:
		 * 
		 */
		$all_paragraphs = preg_split('/(?<=[\r\n])/', $document->text);
		array_walk($all_paragraphs, 'self::trim_value');
		$all_paragraphs = array_filter($all_paragraphs, function($value) { return $value !== ''; });

		// $dialogue_sentences = array();
		// $non_dialogue_sentences = array();
		$sentences_in_paragraphs = array();
		$document->dialogue_paragraph_count = 0;
		$document->non_dialogue_paragraph_count = 0;
		
		foreach($all_paragraphs as $paragraph){
			if(strpos($paragraph, '"') !== false || strpos($paragraph, '“') !== false){
				$document->dialogue_paragraph_count++;
				
			}
			else{
				$document->non_dialogue_paragraph_count++;
				
			}
			
			//note \p{Lu} in the following line matches any uppercase letter that has a lowercase variant, \p{L} is any letter in any language
			$sentences = preg_split('/(?<=[”.?!\r\n]|-)(?<![\s"“\']\p{Lu}.|\s\p{Lu}[a-z].)(?=[\s\'"”“]+\p{Lu}|"|\d|“)(?![\'”"])/', $paragraph);

			if(isset($sentences_in_paragraphs[count($sentences)])){
				$sentences_in_paragraphs[count($sentences)] ++;
			}
			else{
				$sentences_in_paragraphs[count($sentences)] = 1;
			}
			
			
			foreach($sentences as $sentence){
				$sentence_list[] = $sentence;
				
			}
			
		}

		unset($all_paragraphs);
		
		/*
		 * Sentence-scope processing:
		 * 
		 */
		array_walk($sentence_list, 'self::trim_value');
		array_walk($sentence_list, 'self::convert_string_mb');
		$sentence_list = array_filter($sentence_list, function($value) { return $value !== '' && $value !== '?';});
		
		$document->sentence_count = count($sentence_list);
		
		if($document->sentence_count > 0){
			$document->period_end_count = 0;
			$document->question_end_count = 0;
			$document->exclaim_end_count = 0;

			$sentence_length_vs_commas = array();
			$sentence_lengths = array();
			$word_lengths = array();
			$total_sentence_characters = 0;
			
			foreach($sentence_list as $sentence){
				// ini_set('mbstring.substitute_character', "none"); 
				// $sentence = mb_convert_encoding($sentence, 'ASCII', 'UTF-8'); 
				// $sentence = iconv("UTF-8", "UTF-8//IGNORE", $sentence);
				// $sentence = mb_convert_encoding(trim($sentence), "UTF-8");
				// dd("dd " . mb_detect_encoding($sentence)); 
				
				
				//Tally up end-sentence punctuation
				$last_char = substr($sentence, -1);
				if($last_char == '"' || $last_char == '”' || $last_char == '\''){//(if the sentence ends with a quote, use second to last character instead)
					$last_char = substr($sentence, -2, 1);
				}
				if($last_char == '.'){
					$document->period_end_count++;
				}
				elseif($last_char == '!'){
					$document->exclaim_end_count++;
				}
				elseif($last_char == '?'){
					$document->question_end_count++;
				}
				
				$words_in_sentence = preg_split('/([\s,;.!?()"“”–]+)/', $sentence);
				array_walk($words_in_sentence, 'self::trim_value');
				array_walk($words_in_sentence, 'self::convert_string');
				
				$words_in_sentence = array_filter($words_in_sentence, function($value){return $value !== '' /*&& iconv_strlen($value) > 1*/;});
				if(count($words_in_sentence) > 0){
					array_push($raw_word_list, ...$words_in_sentence);
				}
				
				//adding to word_lengths list
				foreach($words_in_sentence as $word){
					$word_length = strlen($word);
					if($word_length > 0){
						if(isset($word_lengths[$word_length])){
							$word_lengths[$word_length] ++;
						}
						else{
							$word_lengths[$word_length] = 1;
						}
					}
				}
				
				//adding to frontwords frequency list
				if(count($words_in_sentence) >= 1){
					if(isset($sentence_frontwords[reset($words_in_sentence)])){
						$sentence_frontwords[reset($words_in_sentence)] ++;
					}
					else{
						$sentence_frontwords[reset($words_in_sentence)] = 1;
					}
				}
				
				//adding to non frontwords frequency list
				if(count($words_in_sentence) >= 2){
					foreach($words_in_sentence as $word){
						if($word !== reset($words_in_sentence)){
							if(isset($sentence_non_frontwords[$word])){
								$sentence_non_frontwords[$word] ++;
							}
							else{
								$sentence_non_frontwords[$word] = 1;
							}
						}
					}
				}
				
				//adding to sentence_lengths list
				if(!isset($sentence_lengths[count($words_in_sentence)])){
					$sentence_lengths[count($words_in_sentence)] = 1;
				}
				else{
					$sentence_lengths[count($words_in_sentence)]++;
				}
				
				//comma vs sentence length graph data
				$commas_in_sentence = substr_count($sentence, ',');
				if(isset($sentence_length_vs_commas[count($words_in_sentence)])){
					$sentence_length_vs_commas[count($words_in_sentence)] += $commas_in_sentence;
				}
				else{
					$sentence_length_vs_commas[count($words_in_sentence)] = $commas_in_sentence;
				}
				
				//building sentence lengths stats
				if(!isset($document->longest_sentence_content) || strlen($sentence) > strlen($document->longest_sentence_content)){
					$document->longest_sentence_word_count = count($words_in_sentence);
					$document->longest_sentence_content = $sentence;
				}
				if(!isset($document->shortest_sentence_content) || strlen($sentence) < strlen($document->shortest_sentence_content)){
					$document->shortest_sentence_word_count = count($words_in_sentence);
					$document->shortest_sentence_content = $sentence;
				}
				
				$total_sentence_characters += strlen($sentence);
			}
			unset($sentence_list);
			
			$document->average_sentence_character_length = $total_sentence_characters / $document->sentence_count;
			$document->average_sentence_word_length = count($raw_word_list) / $document->sentence_count;			
			
			//Create comma vs sentence string for chart
			ksort($sentence_length_vs_commas);
			if(count($sentence_length_vs_commas) <= 1){
				$document->commas_per_sentence = '';
			}
			else{
				$document->commas_per_sentence = '[["Sentence Length", "Commas"],';
				foreach($sentence_length_vs_commas as $length => $comma_count){
					$comma_count /= max(1, $sentence_lengths[$length]);
					$document->commas_per_sentence .= '[' . $length . ', ' . $comma_count . '],';
				}
				$document->commas_per_sentence = substr($document->commas_per_sentence, 0, strlen($document->commas_per_sentence) - 1) . ']';
			
			}
			
			//Create word_lengths string for chart
			if(count($word_lengths) <= 1){
				$document->word_lengths = '';
			}
			else{
				$document->word_lengths = '[["Word Length", "Quantity"],';
				foreach($word_lengths as $length => $quantity){
					$document->word_lengths .= '[' . $length . ', ' . $quantity . '],';
				}
				$document->word_lengths = substr($document->word_lengths, 0, strlen($document->word_lengths)-1) . ']';
			}
			
			//Create sentence_lengths string for chart
			$document->sentence_lengths = self::histogram_string_from_array($sentence_lengths, 'Sentence Lengths');
			
			//Create paragraph_lengths string for chart
			$document->paragraph_lengths = self::histogram_string_from_array($sentences_in_paragraphs, 'Paragraph Length', 1);
			
		}
		
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
		if(count($raw_word_list) > 0){
			//Create word frequency list
			$total_chars = 0;
			foreach($raw_word_list as $word){
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
			
			if($document->word_count === 0){
				return self::stop_on_error('Word count must be greater than 0.');
			}
			
			$document->unique_word_count = count($word_frequency_list);
			$document->average_word_length = $total_chars / max($document->word_count, 1);
			arsort($word_frequency_list);
			
			//create a postgres stoplist version
			foreach($word_frequency_list as $word => $frequency){
				if(!self::is_in_stoplist($word)){
					$word_list_postgres_stoplist[$word] = $frequency;
				}
			}
			$word_list_postgres_stoplist = array_slice($word_list_postgres_stoplist, 0, 50, true);
			
			$word_frequency_list = array_slice($word_frequency_list, 0, 50, true);
			
		}
		
		$proper_names = array();
		if(count($sentence_non_frontwords) > 0){
			//Create proper nouns list
			//TODO: how to filter out start of dialogue following an opening tag, IE [He smiled and said, "Sure."]
			
			foreach($sentence_non_frontwords as $word => $frequency){
				
				if(!self::is_in_stoplist($word)){
					$char = $word[0];
					if($char >= 'A' && $char <= 'Z' && strlen($word) >= 2 && $word[1] !== "'"){
						if(strpos($word, '\'s') !== false){
							$word = substr($word, 0, strpos($word, '\'s'));
						}
						
						if(isset($proper_names[$word])){
							$proper_names[$word] += $frequency;
						}
						else{
							$proper_names[$word] = $frequency;
						}
					}
				}
			}
			foreach($sentence_frontwords as $word => $frequency){
				if(isset($proper_names[$word])){
					$proper_names[$word] += $frequency;
				}
			}
			arsort($proper_names);
			$proper_names = array_slice($proper_names, 0, 50, true);

			//Sorted top frontwords array
			arsort($sentence_frontwords);
			$sentence_frontwords = array_slice($sentence_frontwords, 0, 50, true);
			
		}
		
		$document->average_paragraph_word_count = round($document->word_count / ($document->non_dialogue_paragraph_count + $document->dialogue_paragraph_count)*100)/100;
		
		/*
		 *  db category ids:
		 *	0 - word
		 *	1 - light stoplist
		 *  2 - heavy stoplist
		 *  3 - proper names
		 *
		 */
		
		$new_document_id = null;
		$insertable_word_frequencies = array();
		$insertable_stoplisted_word_frequencies = array();
		$insertable_frontword_frequencies = array();
		$insertable_proper_frequencies = array();
		
		DB::transaction(function() use (&$new_document_id, $document, $word_frequency_list, $word_list_postgres_stoplist, $sentence_frontwords, $insertable_word_frequencies, $proper_names, $insertable_stoplisted_word_frequencies, $insertable_frontword_frequencies, $insertable_proper_frequencies){
			//Insert document
			$new_document_id = DB::table('documents')->insertGetId($document['attributes']);
			
			//Prepare the word data
			foreach($word_frequency_list as $word => $frequency){
				$insertable_word_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency, 'category_id' => 0];
			}	

			foreach($word_list_postgres_stoplist as $word => $frequency){
				$insertable_stoplisted_word_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency, 'category_id' => 1];
			}				
			
			foreach($sentence_frontwords as $word => $frequency){
				$insertable_frontword_frequencies[] = ['document_id' => $new_document_id, 'word' => $word, 'quantity' => $frequency];
			}				
			
			foreach($proper_names as $word => $frequency){
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
		
		//Redirect:
		if(!isset($new_document_id)){
			return Redirect::to('home')->with('message','<p class="bg-danger">Error.</p>');
		}
		return Redirect::to("/document/$new_document_id")->with('message', '<p class="bg-success">Document uploaded successfully.</p>');
			
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
	
}
