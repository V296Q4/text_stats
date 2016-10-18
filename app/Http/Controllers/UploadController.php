<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Input;
use Redirect;

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
		
		return view('upload');
	}

    /**
     * Handle submitted documents.
     *
     */
    public function submitted(Request $request)
    {
		if(Auth::user()){
			$title = Input::get('title');
			$document = Input::get('document');
			$character_count = strlen($document);
			$type = Input::get('type');
			$is_private = Input::get('is_private');
			$user_id = Auth::user()->id;
			$created_at = date('Y-m-d H:i:s');
			
			//Word-scope processing:
			$word_list = explode(' ', $document);//need to include \n\t\r stuff
			$unique_word_list = array_unique($word_list);
			$unique_word_count = count($unique_word_list);
			$word_count = count($word_list);
			unset($word_list);
			
			//Paragraph-scope processing:
			$paragraph_list = explode('\n', $document);
			$paragraph_count = count($paragraph_list);
			
			//Create in DB and redirect:
			$document_insert_content = [
				'title' => $title, 
				'is_private' => $is_private,
				'created_at' => $created_at,
				'document' => $document, 
				'type' => $type, 
				'created_by' => $user_id, 
				'word_count' => $word_count, 
				'character_count' => $character_count, 
				'unique_word_count' => $unique_word_count,
				'paragraph_count' => $paragraph_count,
				
				
			];
			
			$newDocumentId = DB::table('texts')->insertGetId($document_insert_content);
			if($newDocumentId !== null){
				return Redirect::to("/document/" . $newDocumentId);
			}
			else{
				return Redirect::to("home");
			}
			
		}
        return view('home');
    }
}
