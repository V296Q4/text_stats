<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class ViewDocumentController extends Controller
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
	
	public function random(){
		$random_document = DB::table('texts')->select('id')->inRandomOrder()->first();
		$this->index($random_document->id);
	}

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
		$document = DB::table('texts')->join('users', 'users.id', '=', 'texts.created_by')->select('texts.title', 'texts.version', 'texts.type', 'texts.is_private', 'texts.word_count', 'texts.unique_word_count', 'texts.character_count', 'texts.paragraph_count', 'texts.created_at', 'users.name as created_by')->where('texts.id', $id)->first();
		
		if($document->character_count > 0 && $document->word_count > 0){
			$document->average_word_length = $document->character_count / $document->word_count;
		}
		else{
			$document->average_word_length = 0;
		}
		
		
		$main_text = '<p>Document Version: ' . $document->version . '</p><p>Document Type: ' . $document->type . '</p><p>Is Private: ' . $document->is_private . '</p><p title="Includes Spaces">Character Count: ' . $document->character_count . '</p><p>Word Count: ' . $document->word_count . '</p><p>Unique Word Count: ' . $document->unique_word_count . '</p><p>Average Word Length: ' . $document->average_word_length . '</p>';
		$main_text .= '<p>Paragraph Count: ' . $document->paragraph_count;
		$main_text .= '<p>Created by \'' . $document->created_by . '\' on ' . $document->created_at . '.</p>';
		
        return view('document', ['title' => $document->title, 'main_text' => $main_text]);
    }
}
