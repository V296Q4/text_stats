<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Input;
use Redirect;
use Auth;

class CollectionController extends Controller
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
	
	public function view_owned_collections(){
		$collections = DB::table('collections as c')->join('users', 'users.id', '=', 'c.created_by')->select('c.id', 'c.name', 'c.description', 'c.is_private', 'c.created_by')->where('users.id', Auth::id())->get();
		$collection_count = sizeof($collections);

		if($collection_count > 0){
		
			$table = 'You have ' . $collection_count . ' collection' . ($collection_count > 1 ? 's' : '') . '.<br><table class="table table-striped table-responsive"><thead><tr><th>Name</th><th>Description</th><th>Private</th><th></th></tr></thead><tbody>';
			
			foreach($collections as $collection){
				$table .= '<tr><td><a href="/collection/' . $collection->id . '">' . $collection->name . '</a></td><td>' . $collection->description . '</td><td class="col-md-1">' . ($collection->is_private ? 'Yes' : 'No') . '</td><td class="col-md-1"><a href="/delete_collection/' . $collection->id . '" type="button" class="btn btn-danger btn-sm">Delete</a></td></tr>';
			}
			
			$table .= '</tbody></table>';
		}
		else{
			$table = 'No Collections Found.';
		}

        return view('collections', ['table' => $table]);
		
	}
	
	public function view_collection($id){
		$collection_id = intval($id);
		$collection = DB::table('collections as c')->select('name', 'description', 'is_private', 'created_at', 'created_by', 'edited_at')->where('id', $collection_id)->first();
		
		if(!isset($collection)){
			return Redirect::to("/home/")->with('message', '<p class="bg-warning">Collection does not exist.</a>.</p>');
		}
		
		$main_heading = 'Collection "' . $collection->name . '"<br><small>(ID:' . $collection_id . ')</small>';
		$user_id = Auth::id();
		if($collection->is_private && Auth::id() !== $collection->created_by){
			return Redirect::to("/home/")->with('message', '<p class="bg-warning">Collection is private.</a>.</p>');
		}
		else{
			if(Auth::id() == $collection->created_by){
				$is_owner = true;
			}
			else{
				$is_owner = false;
			}
			
			$collection_documents = DB::table('collection_document as cd')->join('documents as d', 'd.id', '=', 'cd.document_id')->where('cd.collection_id', $collection_id)->select('d.id', 'd.title', 'd.word_count', 'cd.comment')->get();
		}
		
		$table = '<p>' . sizeof($collection_documents) . ' document' . (sizeof($collection_documents) == 1 ? '' : 's') . ' in the collection.</p>';
		
		if(sizeof($collection_documents) > 0){
			$table .= '<table class="table table-striped"><thead><tr><th>Name</th><th>Word Count</th><th>Comment</th><th></th></tr></thead><tbody>';
			foreach($collection_documents as $document){
				$table .= '<tr><td><a href="/document/' . $document->id . '">' . $document->title . '</a></td><td class="col-md-2">' . $document->word_count . '</td><td>' . $document->comment . '</td><td class="col-md-1"><a href="/collection/' . $collection_id . '/remove/' . $document->id . '" type="button" class="btn btn-danger btn-sm">Remove</a></td></tr>';
			}
			$table .= '</tbody></table>';
		}
		
		return view('collection', ['table' => $table, 'main_heading' => $main_heading, 'description' => $collection->description, 'is_owner' => $is_owner]);
	}
	
	public function create_collection(){
		return view('create_collection');
	}
	
	public function delete_collection($id){
		$user_id = Auth::id();
		$collection_id = intval($id);
		$collection = DB::table('collections')->select('name', 'created_by')->where('id', $collection_id)->first();
		
		if(!isset($collection)){
			$message = '<p class="bg-warning">Collection does not exist.</p>';
		}
		else{
			if($user_id === $collection->created_by){
				DB::table('collections')->where('id', $collection_id)->delete();
				$message = "<p class='bg-success'>Deleted collection '$collection->name' (ID: $collection_id)</p>";
			}
			else{
				$message = '<p class="bg-warning">You have insufficient permissions.</p>';
			}
		}
		return Redirect::to('/collections')->with('message', $message);
	}
	
	public function create_submitted(Request $request){
		$name = substr(filter_var(trim(Input::get('name')), FILTER_SANITIZE_STRING), 0, 128);

		$name_preexists = DB::table('collections')->select('id')->where('name', $name)->first();
		if(isset($name_preexists)){
			return Redirect::to("/collections")->with('message', '<p class="bg-warning">A collection already exists with the name "' . $name . '".</p>');
		}
		
		$description = substr(filter_var(trim(Input::get('description')), FILTER_SANITIZE_STRING), 0, 1024);
		if(strlen($description) == 0){
			$description = null;
		}
		
		$is_private = Input::get('is_private') ? true : false;
		$created_by = intval(Auth::user()->id);
		$created_at = date('Y-m-d H:i:s');
		
		$collection_insert_content = [
			'name' => $name,
			'description' => $description,
			'is_private' => $is_private,
			'created_by' => $created_by,
			'created_at' => $created_at,
		
		];
		
		$new_collection_id = DB::table('collections')->insertGetId($collection_insert_content);
		if($new_collection_id !== null){
			return Redirect::to("/collections")->with('message', '<p class="bg-success">Collection "<a href="/collection/' . $new_collection_id . '">' . $name . '</a>" created.</p>');
		}
		else{
			return Redirect::to('home')->with('message', '<p class="bg-warning">Error creating collection.</p>');
		}
		
	}
	
	function is_collection_owner($user_id, $collection_id){
		$collection = DB::table('collections')->select('id')->where([['id', intval($collection_id)],['created_by', intval($user_id)]])->first();
		if(isset($collection)){
			return true;
		}
		return false;
	}
	
	public function remove_from_collection($collection_id, $document_id){
		$collection_id = intval($collection_id);
		$document_id = intval($document_id);
		
		//Check permissions:
		if(!CollectionController::is_collection_owner(Auth::id(), $collection_id)){
			return Redirect::to('/home')->with('message', '<p class="bg-warning">Insufficient permissions.</p>');
		}
		
		DB::table('collection_document')->where([['collection_id', $collection_id],['document_id', $document_id]])->delete();
		return Redirect::to('/collection/' . $collection_id)->with('message', '<p class="bg-success">Document removed from collection.</p>');
	}
	
	public function add_to_collection(Request $request){
		$collection_id = intval($request->collection_id);
		$document_id = intval($request->document_id);
		$comment = substr(filter_var(trim(Input::get('comment')), FILTER_SANITIZE_STRING), 0, 2048);
		if($comment == 0){
			$comment = null;
		}
		
		//Check permissions:
		$collection = DB::table('collections')->select('id', 'created_by', 'name')->where('id', $collection_id)->first();
		if(isset($collection) && $collection->created_by == Auth::id()){
			
			//Check document doesn't already exist in the collection:
			$collection_document = DB::table('collection_document')->select('collection_id')->where([['collection_id', $collection_id], ['document_id', $document_id]])->first();

			if(isset($collection_document)){
				return Redirect::to("/document/" . $document_id)->with('message', '<p class="bg-warning">Document already exists in the collection <a href="/collection/' . $collection->id . '">"' . $collection->name . '"</a>.</p>')->withInput();

			}
			else{
				$collection_document_insert_content = [
					'collection_id' => $collection_id,
					'document_id' => $document_id,
					'created_at' => date('Y-m-d H:i:s'),
					'comment' => $comment,
				];
				DB::table('collection_document')->insert($collection_document_insert_content);
				return redirect()->back()->with('message', '<p class="bg-success">Document added to collection <a href="/collection/' . $collection->id . '">"' . $collection->name . '"</a>.</p>');
				
			}
		}
		else{
			return Redirect::to('/home')->with('message', '<p class="bg-warning">Insufficient permissions.</p>');
		}
	}
	
}
