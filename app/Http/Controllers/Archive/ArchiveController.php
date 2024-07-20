<?php

namespace App\Http\Controllers\Archive;

use App\Helpers\FetchAllPost;
use App\Http\Controllers\Controller;
use App\Models\Archive;
use App\Models\LogActivity;
use Auth;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

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
     *     path="/api/archive",
     *     summary="Archive Post",
     *     tags={"FetchPost"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"authorType" ,"postId","partyId"},
     *         @OA\Property(property="authorType", type="string", example="Leader/Party"),
     *        @OA\Property(property="postId", type="string", example="dfdf56dfd"),
     *        @OA\Property(property="partyId", type="string", example="dfdf56dfd"),
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
    public function store(Request $request)
    {
        try {
            $authorType = $request->authorType;
            $postId = $request->postId;
            $partyId = $request->partyId;
            $userId = $partyId ? $partyId : Auth::user()->id;
            $rolename = Auth::user()->getRoleNames()[0];
            $rolename = $partyId ? "Party" : $rolename;

            $archieveContent = [
                "authorType" => $authorType,
                "postId" => $postId,
                "archiveByType" => $rolename,
                "archiveById" => $userId,
                "createdBy" => Auth::user()->id,
                "updatedBy" => Auth::user()->id
            ];
            $existingArchive = Archive::where('postId', $postId)
                ->where('archiveById', $userId)
                ->first();

            if (!$existingArchive) {
                Archive::create($archieveContent);
                return response()->json(['status' => 'success', 'message' => 'Post archive successfully'], 200);

            } else {
                return response()->json(['status' => 'error', 'message' => 'Post already archived'], 404);
            }


        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/getAllArchievePost",
     *     summary="Get All Archieve Post",
     *     tags={"FetchPost"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId"},
     *         @OA\Property(property="partyId", type="string", example="partyId"),
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
    public function getAllArchievePost(Request $request)
    {
        $partyId = $request->partyId;
        $userId = $partyId ? $partyId : Auth::user()->id;
        $currentPage = request('page', 1);
        $archiveById = Archive::where('archiveById', $userId)->pluck('postId');
        if(count( $archiveById)>0)
        {
            $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, $archiveById,null,null);
        }
        else{
            $allPost = [] ; 
        }
        return response()->json(['status' => 'success', 'message' => "Your Archieve Post", "result" => $allPost], 200);

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

          /**
     * @OA\Delete(
     *     path="/api/archive/{id}",
     *     summary="Delete archive  id",
     *     tags={"FetchPost"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *       response="400",
     *       description="Bad Request",
     *   ),
     *   @OA\Response(
     *       response="401",
     *       description="Data not found",
     *   ),
     *  security={{ "apiAuth": {} }}
     * )
     */
    public function destroy(string $id)
    {
        try {
            $partyId = request('partyId');
            $userId = $partyId ? $partyId : Auth::user()->id;
            Archive::where('postId',$id)->where('archiveById',$userId)->delete();
            return response()->json(['status' => 'success', 'message' => 'Post UnArchieve'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


}
