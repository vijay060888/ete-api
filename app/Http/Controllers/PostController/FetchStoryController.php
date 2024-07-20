<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\FetchAllStory;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\StoryByCitizen;
use App\Models\StoryByLeader;
use App\Models\StoryByParty;
use Auth;
use Illuminate\Http\Request;
use App\Models\StoryByLeaderViews;
use App\Models\StoryByPartyViews;

class FetchStoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/fetchallStory",
     *     summary="Fetch all Stories from your followee",
     *     tags={"FetchPost"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
         try {
            $userId = $request->input('partyId', Auth::user()->id);
            $authorType = $userId !== Auth::user()->id ? 'Party' : 'Leader';
            $fetchAllStory = FetchAllStory::getCombinedStories($userId, $authorType);
            return response()->json(['status' => 'success', 'message' => "All Stories Result", "result" => $fetchAllStory], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
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
    /**
     *   @OA\Put(
     *   path="/api/fetchallStory/{storyId}",
     *     summary="View Story by storyId",
     *     tags={"FetchPost"},
     *     @OA\Parameter(
     *         name="storyId",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"authorType"},
     *         @OA\Property(property="authorType", type="string", example="authorType"),
     *        
     *         
     *      ),
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Error while updating "
     *   ),
     *    security={{ "apiAuth": {} }}
     *)
     **/
    public function update(Request $request, string $id)
    {
        try {
            $storyId = $id;
            $storyByType = $request->authorType;
            $userId = Auth::user()->id;

            $storyByViewModelClass = ($storyByType == 'Leader') ? StoryByLeaderViews::class :
                (($storyByType == 'Party') ? StoryByPartyViews::class : null);

            $storyByTypeVariable = ($storyByType == 'Leader') ? "storyByLeaderId" :
                (($storyByType == 'Party') ? "storyByPartyId" : null);

            $existingView = $storyByViewModelClass::where("$storyByTypeVariable", $storyId)
                ->where('viewedBy', $userId)
                ->first();

            if (!$existingView) {

                $createStoryView = [
                    "$storyByTypeVariable" => $storyId,
                    "viewedBy" => $userId,
                    'createdBy' => $userId,
                    'updatedBy' => $userId,
                ];
                $storyByViewModelClass::create($createStoryView);
            }
            return response()->json(['status' => 'success', 'message' => "Story Viewed"], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/fetchallStory/{storyId}",
     *     summary="Delete story by storyid",
     *     tags={"FetchPost"},
     *     @OA\Parameter(
     *         name="storyId",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *         description="Author Type",
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="authorType",
     *                     type="string",
     *                     example="Leader/Party"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 @OA\Property(
     *                     property="authorType",
     *                     type="string",
     *                     example="Leader/Party"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad Request",
     *     ),
     *     @OA\Response(
     *         response="401",
     *         description="Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function destroy(string $id, Request $request)
    {
        try {
            $storyId = $id;
            $postType = $request->authorType;

            $storyByModelClass = ($postType == 'Leader') ? StoryByLeader::class :
                (($postType == 'Citizen') ? StoryByCitizen::class :
                    (($postType == 'Party') ? StoryByParty::class : null));

            $story = $storyByModelClass::where('id', $storyId)->first();
            $story->delete();
            return response()->json(['status' => 'success', 'message' => 'Story Delete Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
}