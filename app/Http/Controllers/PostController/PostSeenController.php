<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\PostSeen;
use Auth;
use Illuminate\Http\Request;

class PostSeenController extends Controller
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
     *     path="/api/postseen",
     *     summary="Mark Post Seen",
     *     tags={"FetchPost"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"authorType","postId"},
     *         @OA\Property(property="authorType", type="string", example="Authour Type"),
     *         @OA\Property(property="postId", type="string", example="postId"),
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
        $authorType = $request->authorType;
        $postId = $request->postId;
        $userId = Auth::user()->id;
    
        $postSeen = [
            'id' => \DB::raw('gen_random_uuid()'),
            "userId" => $userId,
            "postByType" => $authorType,
            "postId" => $postId,
            "createdBy" => Auth::user()->id,
            "updatedBy" => Auth::user()->id,
            'createdAt' => now(),
            'updatedAt' => now(),

        ];
    
        PostSeen::updateOrInsert(
            [
                'userId' => $userId,
                'postId' => $postId,
            ],
            $postSeen
        );
        return response()->json(['status' => 'success','message' => 'Post seen'],200);
    }catch (\Exception $e) {
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
