<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Auth;
use Illuminate\Support\Facades\Input;
use Redirect;

class BrowseDocumentsController extends Controller
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

    /**
     * Handle creating Browse view.
     *
     */
    public function index()
    {
		
		$documentList = DB::table('texts')->select('id', 'title', 'type', 'word_count')->get();

		$table = '<table class="table table-striped table-responsive"><thead><tr><th>Title</th><th>Type</th><th>Word Count</th></tr></thead><tbody>';
		foreach($documentList as $doc){
			$table .= '<tr><td><a href="/document/' . $doc->id . '">' . $doc->title . '</a></td><td>' . $doc->type . '</td><td>' . $doc->word_count . '</td></tr>';
		}
		$table .= '</tbody></table>';

        return view('browse', ['table' => $table]);
    }
}
