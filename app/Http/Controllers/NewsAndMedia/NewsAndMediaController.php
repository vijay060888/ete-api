<?php

namespace App\Http\Controllers\NewsAndMedia;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NewsAndMedia;
use Auth;
use App\Helpers\LogActivity;

class NewsAndMediaController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/news-and-media",
     *     summary="Add news and media",
     *     tags={"News And Media"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(property="postByType", type="string", example="Leader/Party"),
     *             @OA\Property(property="newsttitle", type="string", example="News Title"),
     *             @OA\Property(property="newsdescription", type="string", example="News Description"),
     *             @OA\Property(property="costincured", type="integer", example=5000),
     *             @OA\Property(property="hashtags", type="string", example="news, media"),
     *             @OA\Property(
     *                 property="mediaupload",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     example="/path/to/image1.jpg",
     *                 ),
     *                 description="Array of media URLs"
     *             ),
     *             @OA\Property(property="leader_id", type="string", example="leader_id"),
     *             @OA\Property(property="party_id", type="string", example="party_id"),
     *             @OA\Property(property="status", type="string", example="published"),
     *             @OA\Property(property="url", type="string", example="https://example.com"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
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
     *         response=422,
     *         description="Unprocessable Entity",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'newsttitle' => 'required|string',
            'newsdescription' => 'required|string',
            'mediaupload' => 'required|array|min:1', // Ensure media is an array with at least one item
            'mediaupload.*' => 'required|string', // Each media item must be a string
        ]);
        $newsttitle = $validatedData['newsttitle'];
        $newsdescription = $validatedData['newsdescription'];
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
        $newsandmedia = NewsAndMedia::create([
            'newsttitle' => $newsttitle,
            'newsdescription' => $newsdescription,
            'hashtags' => $hashtag,
            'party_id' => $party_id,
            'costincured' => $costincured,
            'leader_id' => $leader_id,
            'status' => $status,
            'url' => $url,
            'mediaupload' => json_encode($validatedData['mediaupload']), // Convert media collection to JSON string for storage
        ]);
        $newsandmediaData = NewsAndMedia::where('id', $newsandmedia->id)->first();
        $newsandmediaData->mediaupload = json_decode($newsandmediaData->mediaupload, true);
        return response()->json(['status' => 'success','message' => 'News and Media created successfully', 'newsandmedia' => $newsandmediaData], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/news-and-media/{id}",
     *     summary="Update news and media",
     *     tags={"News And Media"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the news and media to update",
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
     *             @OA\Property(property="newsttitle", type="string", example="Updated Title"),
     *             @OA\Property(property="newsdescription", type="string", example="Updated Description"),
     *             @OA\Property(property="costincured", type="integer", example=6000),
     *             @OA\Property(property="hashtags", type="string", example="updated, media"),
     *             @OA\Property(property="status", type="string", example="status"),
     *             @OA\Property(
     *                 property="mediaupload",
     *                 type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     example="/path/to/image2.jpg",
     *                 ),
     *                 description="Array of updated media URLs"
     *             ),
     *             @OA\Property(property="url", type="string", example="https://example.com"),
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
     *         description="Not Found",
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'newsttitle' => 'required|string',
            'newsdescription' => 'required|string',
            'mediaupload' => 'required|array|min:1', // Ensure media is an array with at least one item
            'mediaupload.*' => 'required|string', // Each media item must be a string
        ]);

        $newsAndMedia = NewsAndMedia::findOrFail($id);

        $newsAndMedia->update([
            'newsttitle' => $validatedData['newsttitle'],
            'newsdescription' => $validatedData['newsdescription'],
            'hashtags' => $request->hashtags,
            'costincured' => $request->costincured,
            'status' => $request->status,
            'mediaupload' => json_encode($validatedData['mediaupload']), // Convert media collection to JSON string for storage
            'url' => $request->url,
        ]);

        $updatedNewsAndMedia = NewsAndMedia::findOrFail($id);
        $updatedNewsAndMedia->mediaupload = json_decode($updatedNewsAndMedia->mediaupload, true);

        return response()->json(['status' => 'success', 'message' => 'News and Media updated successfully', 'newsAndMedia' => $updatedNewsAndMedia], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/news-and-media/{id}/status",
     *     summary="Update News And Media Status by ID",
     *     tags={"News And Media"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the News And Media to update",
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
             $newsandmedia = NewsAndMedia::findOrFail($id);
             $userType = $request->userType;
             
             if($userType == "Leader"){
                 $archive_by = Auth::user()->id;
                 $archive_by_type = $userType; 
             }else {
                 $archive_by = $request->party_id;
                 $archive_by_type = $userType;
             }
             $newsandmedia->update(
                 [
                     'status' => $validatedData['status'],
                     'archieveby' => $archive_by,
                     'archieveType' => $archive_by_type,
             ]);
             return response()->json(['status' => 'success', 'newsandmedia' => $newsandmedia], 200);
         } catch (\Exception $e) {
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
         }
     }

     /**
     * @OA\Get(
     *     path="/api/news-and-media",
     *     summary="List News And Media",
     *     tags={"News And Media"},
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
                $newsandmedia = NewsAndMedia::where('leader_id', Auth::user()->id)
                                        ->whereIn('status', ['published', 'unpublished'])
                                        ->orderBy('created_at', 'desc')
                                        ->paginate($perPage);
            } elseif ($request->userType == "Party") {
                $newsandmedia = NewsAndMedia::where('party_id', $request->party_id)
                                    ->whereIn('status', ['published', 'unpublished'])
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($perPage);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Invalid user type'], 400);
            }
            foreach ($newsandmedia as $news) {
                $news->mediaupload = json_decode($news->mediaupload);
                $news->currentPage = $newsandmedia->currentPage();
            }
            $currentPage = $newsandmedia->currentPage();
            $lastPage = $newsandmedia->lastPage();
            return response()->json(['status' => 'success', 'newsandmedia' => $newsandmedia,'current_page' => $currentPage,
            'last_page' => $lastPage], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/news-and-media/update/{id}",
     *     summary="Get News and Media by ID",
     *     tags={"News And Media"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the News And Media to update",
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
            $newsandmedia = NewsAndMedia::findOrFail($id);
            if($newsandmedia){
                $media = json_decode($newsandmedia->mediaupload, true);
                $newsandmedia->mediaupload = $media;
                return response()->json(['status' => 'success', 'newsandmedia' => $newsandmedia], 200);
            }else {
                return response()->json(['status' => 'failed', 'newsandmedia' => "not found News"], 404);
            }
            
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

}
