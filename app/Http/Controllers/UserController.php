<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Redirect;
use Auth;

class UserController extends Controller
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
     * Show the user.
     *
     */
    public function index($id)
    {
		$user = DB::table('users as u')->select('name', 'bio', 'created_at')->where('id', $id)->first();

		$main_text = '<p>Username: ' . $user->name . '</p>';
		$main_text .= '<p>' . $user->bio . '</p>';
		$main_text .= "<p>Joined on $user->created_at.</p>";

		//show most recent non-private uploads
		
        return view('user', ['title' => $user->name, 'main_text' => $main_text]);
    }
	
    /**
     * List of browsable users.
     *
     */
	public function browse_users()
    {
		$user_list = DB::table('users')->select('id', 'name')->get();

		//what else?  last seen?  uploads?
		
		$table = '<table class="table table-striped table-responsive"><thead><tr><th>Title</th><th>Type</th><th>Word Count</th></tr></thead><tbody>';
		foreach($user_list as $doc){
			$table .= '<tr><td><a href="/document/' . $doc->id . '">' . $doc->title . '</a></td><td>' . $doc->type . '</td><td>' . $doc->word_count . '</td></tr>';
		}
		$table .= '</tbody></table>';

        return view('browse', ['table' => $table]);
    }
	

	
}
