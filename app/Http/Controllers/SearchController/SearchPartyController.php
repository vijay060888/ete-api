<?php

namespace App\Http\Controllers\SearchController;

use App\Helpers\LogActivity;
use App\Helpers\Search;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SearchPartyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     /**
     * @OA\Post(
     *     path="/api/searchParty",
     *     summary="Search Party",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyword"),
     *         
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
    public function searchParty(Request $request)
    {
        try { 
       $keyword = $request->keyword;
       $result =   Search::searchParty($keyword);
       return response()->json(['status' => 'success', 'message' => 'Party Search Result','result' => $result], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
      /**
     * @OA\Post(
     *     path="/api/searchPartywithAdminAccess",
     *     summary="Search Party with Admin Access",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyword"),
     *         
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
    public function searchPartywithAdminRole(Request $request)
    {
        try { 
       $keyword = $request->keyword;
       $result =   Search::searchPartywithAdminAccess($keyword);
       return response()->json(['status' => 'success', 'message' => 'Party Search Result','result' => $result], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
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
    public function store(Request $request)
    {
        //
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
