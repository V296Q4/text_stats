<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;

class HomeController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
		$documents_analyzed = DB::table('global_stats')->select('value')->where('stat','documents_analyzed')->first()->value;
		$words_analyzed = DB::table('global_stats')->select('value')->where('stat','words_analyzed')->first()->value;
		if(isset($documents_analyzed, $words_analyzed)){
			return view('home', ['documents_analyzed' => $documents_analyzed, 'words_analyzed' => $words_analyzed]);
		}
		return view('home');
    }
	
	public function logout(){
		Auth::logout();
		return Redirect::to('/home')->with('message', '<p class="bg-success">You are now logged out.</p>');
	}
	
}
