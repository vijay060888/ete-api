<?php

namespace App\Http\Controllers\SearchController;

use App\Helpers\FetchAllPost;
use App\Helpers\Search;
use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;

class GlobalSearch extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */

     /**
     * @OA\Post(
     *     path="/api/globalSearch",
     *     summary="Global Search",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"searchType" , "keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyWord"),
     *        @OA\Property(property="searchType", type="string", example="searchType"),
     *      ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *           mediaType="application/json",
     *         )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function store(Request $request)
    {
        try{
        $validator = \Validator::make($request->all(), [
            'keyword' => 'required',
            'searchType' => 'required', 
        ]);
       
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
        } 
        $searchType = $request->searchType;
        $keyword = $request->keyword;
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);

        switch ($searchType) {
            case 'post':
                $result = FetchAllPost::getAllPost($currentPage,$partyId,$keyword,null,null,null,null);
                break;
            case 'constituency':
                $result = Search::constituencySearch($currentPage,$keyword);
                break;
            case 'party':
                $result = Search::partiesSearch($currentPage,$keyword);
                break;
            case 'leaderUser':
                $result = Search::leaderUserSearch($currentPage,$keyword);
                break;
            default:
                return response()->json(['status' => 'error', 'message' => 'Invalid searchType'], 400);
        }
        return response()->json(['status' => 'success','message' => 'Search Result', 'result' => $result],200);
    }catch (\Exception $e) {
        return $e->getMessage();
        LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
        return response()->json(['status' => 'error','message'=>"Server Error"],404);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
