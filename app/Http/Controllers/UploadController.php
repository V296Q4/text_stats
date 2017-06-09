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
		return view('upload', ['character_limit' => self::GetCharacterLimit()]);
	}

	/*
	 * Redirects the user back with an error message.
	 *
	 */
	public function StopOnError($error){
		return back()->with('message', '<p class="bg-danger">Error: ' . $error . '</p>');
	}

	/*
	 * Returns character limit for the user's upload
	 *
	 */	
	public function GetCharacterLimit(){
		return Auth::check() ? 2000000 : 1000000;
	}
	
    /**
     * Handle posted (submitted) documents.
     *
     */
    public function submitted(Request $request)
    {
		$document = new App\Document;
		$document_insert_content = array();
		
		//Basic info:
		$document->title = mb_convert_encoding(substr(filter_var(trim(Input::get('title')), FILTER_SANITIZE_STRING),0,128), "UTF-8");
		$document->description = mb_convert_encoding(substr(filter_var(trim(Input::get('description')), FILTER_SANITIZE_STRING),0,2048), "UTF-8");
		$document->text = mb_convert_encoding(trim(Input::get('text')), "UTF-8");
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
			//return self::StopOnError('Title must be at least 3 characters long.');//TODO but if guest!
		}
		if($document->character_count < 2){
			return self::StopOnError('Text must be at least 2 characters long.');
		}
		if($document->character_count > self::GetCharacterLimit()){//TODO: or just auto cut to limit?
			return self::StopOnError("Text is $document->character_count characters long.  The limit is $character_limit");
		}
		
		$document_insert_content = [
			'title' => $document->title, 
			'description' => $document->description,
			'is_private' => $document->is_private,
			'created_at' => $document->created_at,
			'text' => $document->text, 
			'type' => $document->type, 
			'created_by' => $document->created_by, 
			'character_count' => $document->character_count,
		];
		
		/*
		 * Paragraph-scope processing:
		 * 
		 */
		//$paragraph_list = preg_split('/[\r\n]+((\r|\n|\r\n)[^\r\n]+)*/', $document->text);
		$document->paragraph_count = -1;//count($paragraph_list);
		
		//$paragraph_average_word_length
		//$paragraph_average_sentence_length
		//foreach($paragraph_list as $paragraph){
			//$paragraph_sentences = preg_split('/([\.?!][ \n\r])/', $paragraph);
			
		//}
		
		/*
		 * Sentence-scope processing:
		 * 
		 */
		$raw_word_list = array();
		$sentence_frontwords = array();
		$sentence_non_frontwords = array();
		$sentence_list = preg_split('/(?<=[.?!\r\n]|-)(?<![\s"“\']\p{Lu}.|\s\p{Lu}[a-z].)[\s\'"”“]+(?=\p{Lu}|"|\d|“)/', $document->text);

		$document->sentence_count = sizeof($sentence_list);
		if($document->sentence_count > 0){
			$document->period_end_count = 0;
			$document->question_end_count = 0;
			$document->exclaim_end_count = 0;
			$document->other_end_count = 0;
			
			$sentence_lengths = array();
			$word_lengths = array();
			$total_sentence_characters = 0;
			
			foreach($sentence_list as $sentence){
				//Tally up end-sentence punctuation
				$last_char = substr($sentence, -1);
				//(if the sentence ends with a quote, use second to last character instead)
				if($last_char == '"' || $last_char == '”' || $last_char == '\''){
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
				else{
					$document->other_end_count++;
				}
				
				$words_in_sentence = array_filter(preg_split('/([\s,;.!?()"“”–]+)/', $sentence), function($value){return $value !== '';});
				array_push($raw_word_list, ...$words_in_sentence);
				
				//Word Lengths List
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
				
				//frontwords frequency list
				if(count($words_in_sentence) > 0){
					if(isset($sentence_frontwords[reset($words_in_sentence)])){
						$sentence_frontwords[reset($words_in_sentence)] ++;
					}
					else{
						$sentence_frontwords[reset($words_in_sentence)] = 1;
					}
				}
				
				//Non frontwords frequency list //TODO: this + frontwords list can replace word lengths list?
				if(count($words_in_sentence) > 1){
					for($i = 1; $i < count($words_in_sentence); $i++){//TODO: check encoding? must be utf-8
						if(isset($sentence_non_frontwords[$words_in_sentence[$i]])){
							$sentence_non_frontwords[$words_in_sentence[$i]] ++;
						}
						else{
							$sentence_non_frontwords[$words_in_sentence[$i]] = 1;
						}
					}
				}
				
				//Building sentence_lengths array
				if(!isset($sentence_lengths[count($words_in_sentence)])){
					$sentence_lengths[count($words_in_sentence)] = 1;
				}
				else{
					$sentence_lengths[count($words_in_sentence)]++;
				}
				
				//Building sentence lengths stats
				if(!isset($document->longest_sentence_content) || strlen($sentence) > strlen($document->longest_sentence_content)){
					$document->longest_sentence_word_count = sizeof($words_in_sentence);
					$document->longest_sentence_content = $sentence;
				}
				if(!isset($document->shortest_sentence_content) || strlen($sentence) < strlen($document->shortest_sentence_content)){
					$document->shortest_sentence_word_count = sizeof($words_in_sentence);
					$document->shortest_sentence_content = $sentence;
				}
				
				$total_sentence_characters += strlen($sentence);
			}
			
			$document->average_sentence_character_length = $total_sentence_characters / $document->sentence_count;
			$document->average_sentence_word_length = sizeof($raw_word_list) / $document->sentence_count;			
			
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
			if(count($sentence_lengths) <= 1){
				$document->sentence_lengths = '';
			}
			else{
				$document->sentence_lengths = '[["Sentence Length"],';
				foreach($sentence_lengths as $key => $value){
					for($i = 0; $i < $value; $i++){
						$document->sentence_lengths .= '[' . $key . '],';
					}
				}
				$document->sentence_lengths = substr($document->sentence_lengths, 0, strlen($document->sentence_lengths)-1) . ']';
			}
			
			$document_insert_content += ['sentence_count' => $document->sentence_count, 'period_end_count' => $document->period_end_count, 'question_end_count' => $document->question_end_count, 'exclaim_end_count' => $document->exclaim_end_count, 'longest_sentence_word_count' => $document->longest_sentence_word_count, 'longest_sentence_content' => $document->longest_sentence_content, 'shortest_sentence_word_count' => $document->shortest_sentence_word_count, 'shortest_sentence_content' => $document->shortest_sentence_content, 'average_sentence_word_length' => $document->average_sentence_word_length, 'average_sentence_character_length' => $document->average_sentence_character_length, 'sentence_lengths' => $document->sentence_lengths, 'word_lengths' => $document->word_lengths];
		}
		
		/*
		 * Word-scope processing:
		 * 
		 */
		$word_frequency_list = array();
		$word_list_postgres_stoplist = array();
		$document->word_count = 0;
		$document->longest_word = '';
		if(sizeof($raw_word_list) > 0){
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
				return self::StopOnError('Word count must be greater than 0.');
			}
			
			$document->unique_word_count = count($word_frequency_list);
			$document->average_word_length = $total_chars / max($document->word_count, 1);
			arsort($word_frequency_list);
			
			//Apply postgres stoplist
			foreach($word_frequency_list as $word => $frequency){
				$stoplist = config('stoplist.postgres_stoplist');
				if(!in_array($word, $stoplist)){
					$word_list_postgres_stoplist[$word] = $frequency;
				}
			}
			$word_list_postgres_stoplist = array_slice($word_list_postgres_stoplist, 0, 50, true);
			
			$word_frequency_list = array_slice($word_frequency_list, 0, 50, true);
			
			$document_insert_content += [
				'word_count' => $document->word_count,
				'unique_word_count' => $document->unique_word_count,
				'paragraph_count' => $document->paragraph_count,
				'average_word_length' => $document->average_word_length,
				'longest_word' => $document->longest_word,
			];
			
		}
		
		if(sizeof($sentence_frontwords) > 0 || sizeof($sentence_non_frontwords) > 0){
			//Create proper nouns list
			//TODO: how to filter out start of dialogue following an opening tag, IE [He smiled and said, "Why?"]
			$proper_names = array();
			foreach($sentence_non_frontwords as $word => $frequency){
				$char = $word[0];
				if($char >= 'A' && $char <= 'Z' && strlen($word) >= 2 && $word[1] != "'"){
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
		
		/*
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
		
		//TODO: decide what to do for guests
		if(Auth::Guest()){
			foreach($word_frequency_list as $word => $frequency){
				$insertable_word_frequencies[] = ['word' => $word, 'quantity' => $frequency, 'category_id' => 0];
			}	

			foreach($word_list_postgres_stoplist as $word => $frequency){
				$insertable_stoplisted_word_frequencies[] = ['word' => $word, 'quantity' => $frequency, 'category_id' => 1];
			}				
			
			foreach($sentence_frontwords as $word => $frequency){
				$insertable_frontword_frequencies[] = ['word' => $word, 'quantity' => $frequency];
			}				
			
			foreach($proper_names as $word => $frequency){
				$insertable_proper_frequencies[] = ['word' => $word, 'quantity' => $frequency, 'category_id' => 3];
			}		
			
			//$document->insertable_word_frequencies = $insertable_word_frequencies;
			//$document->insertable_stoplisted_word_frequencies = $insertable_stoplisted_word_frequencies;
			//$document->insertable_frontword_frequencies = $insertable_frontword_frequencies;
			//$document->insertable_proper_frequencies = $insertable_proper_frequencies;
			
			//DocumentController::guest_document($document, $insertable_word_frequencies, $insertable_stoplisted_word_frequencies, $insertable_frontword_frequencies, $insertable_proper_frequencies);
			
			//return redirect()->route('/document', ['document' => $document]);
			//return redirect()->action('DocumentController@guest_document', [$document]);
			return redirect()->action('DocumentController@guest_document', [$document]);
			//return redirect()->action('DocumentController@guest_document', [$document, '$test' => $insertable_word_frequencies, $insertable_stoplisted_word_frequencies, $insertable_frontword_frequencies, $insertable_proper_frequencies]);
		}
		
		DB::transaction(function() use (&$new_document_id, $document, $document_insert_content, $word_frequency_list, $word_list_postgres_stoplist, $sentence_frontwords, $insertable_proper_frequencies, $proper_names, $insertable_stoplisted_word_frequencies, $insertable_frontword_frequencies, $insertable_proper_frequencies){
			//Insert document
			$new_document_id = DB::table('documents')->insertGetId($document_insert_content);
			
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
			
			DB::table('global_stats')->where('stat','documents_analyzed')->increment('value');
			DB::table('global_stats')->where('stat','words_analyzed')->increment('value', $document->word_count);
		});
		
		//Redirect:
		if(!isset($new_document_id)){
			return Redirect::to('home')->with('message','<p class="bg-danger">Error.</p>');
			
		}
		return Redirect::to("/document/$new_document_id")->with('message', '<p class="bg-success">Document uploaded successfully.</p>');
			
    }
}
