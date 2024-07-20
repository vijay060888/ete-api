<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\MasterHashTag;
use Illuminate\Http\Request;

class HashTagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\POST(
     *     path="/api/searchHashTag",
     *     summary="Search Hashtag",
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
    public function searchHashTag(Request $request)
    {
        try {
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $keyword = $request->keyword;
            $hashTag = MasterHashTag::where('hashtag', 'like', "%$keyword%")->select('hashtag')->limit(10)
                ->get();

            return response()->json(['status' => 'success', 'message' => "Result for hashtag", "result" => $hashTag], 200);
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