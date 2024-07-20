<?php

namespace App\Http\Controllers\Achievement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Achievement;
use Auth;
use App\Helpers\LogActivity;

class AchievementController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/achievements",
     *     summary="Add achievements",
     *     tags={"Achievements"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(property="postByType", type="string", example="Leader/Party"),
     *             @OA\Property(property="leader_id", type="string", example="leader_id"),
     *             @OA\Property(property="status", type="string", example="status"),
     *             @OA\Property(property="achievementstitle", type="string", example="achievementstitle"),
     *             @OA\Property(property="achievementsdescription", type="string", example="achievements description"),
     *             @OA\Property(property="costincured", type="string", example="4000"),
     *             @OA\Property(property="party_id", type="string", example="party_id"),
     *             @OA\Property(property="hashtag", type="string", example="hashtag"),
     *             @OA\Property(property="url", type="string", example="url"),
     *             @OA\Property(
    *                 property="mediaupload",
    *                 type="array",
    *                 @OA\Items(
    *                     type="string",
    *                     example="/path/to/image1.jpg",
    *                 ),
    *                 description="Array of media URLs"
    *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'achievementstitle' => 'required|string',
            'achievementsdescription' => 'required|string',
            'mediaupload' => 'required|array|min:1', // Ensure media is an array with at least one item
            'mediaupload.*' => 'required|string', // Each media item must be a string
        ]);
        $achievementstitle = $validatedData['achievementstitle'];
        $achievementsdescription = $validatedData['achievementsdescription'];
        $hashtag = $request->hashtag;
        $postType = $request->postByType;
        $costincured = $request->costincured;
        $url = $request->url;
        $party_id = null;
        $leader_id = null;
        if($postType == "Leader"){
            $leader_id = Auth::user()->id;
        }else {
            $party_id = $request->party_id;
        }
        $status = $request->status;
        $achievement = Achievement::create([
            'achievementtitle' => $achievementstitle,
            'achievementdescription' => $achievementsdescription,
            'hashtags' => $hashtag,
            'party_id' => $party_id,
            'costincured' => $costincured,
            'leader_id' => $leader_id,
            'status' => $status,
            'url' => $url,
            'mediaupload' => json_encode($validatedData['mediaupload']), // Convert media collection to JSON string for storage
        ]);
        $achievementData = Achievement::where('id', $achievement->id)->first();
        $achievementData->mediaupload = json_decode($achievementData->mediaupload, true);
        return response()->json(['status' => 'success','message' => 'Achievement created successfully', 'achievement' => $achievementData], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/achievements/{id}",
     *     summary="Update achievement",
     *     tags={"Achievements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the achievement to update",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(property="achievementstitle", type="string", example="New Title"),
     *             @OA\Property(property="achievementsdescription", type="string", example="New Description"),
     *             @OA\Property(property="costincured", type="integer", example=5000),
     *             @OA\Property(property="hashtag", type="string", example="new_hashtag"),
     *             @OA\Property(property="status", type="string", example="status"),
     *             @OA\Property(property="url", type="string", example="url"),
     *             @OA\Property(
     *                 property="mediaupload",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     example="/path/to/image1.jpg",
     *                 ),
     *                 description="Array of updated media URLs"
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'achievementstitle' => 'required|string',
            'achievementsdescription' => 'required|string',
            'mediaupload' => 'required|array|min:1', // Ensure media is an array with at least one item
            'mediaupload.*' => 'required|string', // Each media item must be a string
        ]);
        $achievement = Achievement::findOrFail($id);
        $achievement->update([
            'achievementtitle' => $validatedData['achievementstitle'],
            'achievementdescription' => $validatedData['achievementsdescription'],
            'hashtags' => $request->hashtag,
            'costincured' => $request->costincured,
            'url' => $request->url,
            'status' => $request->status,
            'mediaupload' => json_encode($validatedData['mediaupload']), // Convert media collection to JSON string for storage
        ]);
        $updatedAchievement = Achievement::findOrFail($id);
        $updatedAchievement->mediaupload = json_decode($updatedAchievement->mediaupload, true);
        return response()->json(['status' => 'success', 'message' => 'Achievement updated successfully', 'achievement' => $updatedAchievement], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/achievements/{id}/status",
     *     summary="Update Achievements Status by ID",
     *     tags={"Achievements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the achievements to update",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid",
     *             example=""
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", example="published/unpublished/archived", description="Achievements status (unpublished, published, or archived)"),
     *             @OA\Property(property="userType", type="string", example="Leader/Party", description="Leader/Party"),
     *             @OA\Property(property="party_id", type="string", example="", description="party_id"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request / Invalid status or missing ID",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */



    public function updateStatus(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'status' => 'required|string', // Assuming status can be one of these values
            ]);
            $achievements = Achievement::findOrFail($id);
            $userType = $request->userType;
            
            if($userType == "Leader"){
                $archive_by = Auth::user()->id;
                $archive_by_type = $userType; 
            }else {
                $archive_by = $request->party_id;
                $archive_by_type = $userType;
            }
            $achievements->update(
                [
                    'status' => $validatedData['status'],
                    'archieveby' => $archive_by,
                    'archieveType' => $archive_by_type,
            ]);
            return response()->json(['status' => 'success', 'achievements' => $achievements], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/achievements",
     *     summary="List Achievements",
     *     tags={"Achievements"},
     *     @OA\Parameter(
     *         name="userType",
     *         in="query",
     *         required=true,
     *         description="User type (Leader or Party)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="leader_id",
     *         in="query",
     *         description="Leader ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="party_id",
     *         in="query",
     *         description="Party ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request / Invalid user type",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */

    public function index(Request $request)
    {
        try {
            $perPage = 10;
            if ($request->userType == "Leader") {
                $achievements = Achievement::where('leader_id', Auth::user()->id)
                                        ->whereIn('status', ['published', 'unpublished'])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate($perPage);
            } elseif ($request->userType == "Party") {
                $achievements = Achievement::where('party_id', $request->party_id)
                                    ->whereIn('status', ['published', 'unpublished'])
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($perPage);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid user type'], 400);
            }
            foreach ($achievements as $achievement) {
                $achievement->mediaupload = json_decode($achievement->mediaupload);
                $achievement->currentPage = $achievements->currentPage();
            }
            $currentPage = $achievements->currentPage();
            $lastPage = $achievements->lastPage();
            return response()->json(['status' => 'success', 'achievements' => $achievements,'current_page' => $currentPage,
            'last_page' => $lastPage], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/achievements/update/{id}",
     *     summary="Get achievements by ID",
     *     tags={"Achievements"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the Achievements to update",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid",
     *             example=""
     *         )
     *     ),
     *     
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request / Invalid status or missing ID",
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */



     public function edit(Request $request, $id)
     {
         try {
             $achievements = Achievement::findOrFail($id);
             if($achievements){
                 $media = json_decode($achievements->mediaupload, true);
                 $achievements->mediaupload = $media;
                 return response()->json(['status' => 'success', 'achievements' => $achievements], 200);
             }else {
                 return response()->json(['status' => 'failed', 'achievements' => "not found Achievements"], 404);
             }
             
         } catch (\Exception $e) {
             return $e->getMessage();
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
         }
     }
}
