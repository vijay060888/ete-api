<?php

namespace App\Http\Controllers\Gallery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Gallery;
use App\Helpers\LogActivity;

class GalleryController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/galleries",
     *     summary="Add Galleries",
     *     tags={"Gallery"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(property="postByType", type="string", example="Leader/Party"),
     *             @OA\Property(property="leader_id", type="string", example="leader_id"),
     *             @OA\Property(property="title", type="string", example="title"),
     *             @OA\Property(property="status", type="string", example="status"),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="party_id", type="string", example="party_id"),
     *             @OA\Property(property="party_admin_id", type="string", example="party_admin_id"),
     *             @OA\Property(property="hashtag", type="string", example="hashtag"),
     *             @OA\Property(
    *                 property="media",
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
        try{
            $validatedData = $request->validate([
                'title' => 'required|string',
                'description' => 'required|string',
                'media' => 'required|array|min:1', // Ensure media is an array with at least one item
                'media.*' => 'required|string', // Each media item must be a string
            ]);
            $title = $validatedData['title'];
            $description = $validatedData['description'];
            $hashtag = $request->hashtag;
            $postType = $request->postByType;
            $party_id = null;
            $leader_id = null;
            if($postType == "Leader"){
                $leader_id = Auth::user()->id;
            }else {
                $party_id = $request->party_id;
            }
            $status = $request->status;
            $gallery = Gallery::create([
                'title' => $title,
                'description' => $description,
                'hashtag' => $hashtag,
                'party_id' => $party_id,
                'leader_id' => $leader_id,
                'status' => $status,
                'media' => json_encode($validatedData['media']), // Convert media collection to JSON string for storage
            ]);
            $galleryData = Gallery::where('id', $gallery->id)->first();
            $galleryData->media = json_decode($galleryData->media, true);
            return response()->json(['status' => 'success', 'gallery' => $galleryData], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/galleries/{id}",
 *     summary="Update Gallery by ID",
 *     tags={"Gallery"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the gallery to update",
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
 *             required={"description", "title", "media"},
 *             @OA\Property(property="postByType", type="string", example="Leader/Party"),
 *             @OA\Property(property="leader_id", type="string", example="leader_id"),
 *             @OA\Property(property="title", type="string", example="title"),
 *             @OA\Property(property="description", type="string", example="description"),
 *             @OA\Property(property="status", type="string", example="status"),
 *             @OA\Property(property="party_id", type="string", example="party_id"),
 *             @OA\Property(property="party_admin_id", type="string", example="party_admin_id"),
 *             @OA\Property(property="hashtag", type="string", example="hashtag"),
 *             @OA\Property(
 *                 property="media",
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
public function edit(Request $request, $id)
{
    try {
        $gallery = Gallery::findOrFail($id);
        $validatedData = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'media' => 'required|array|min:1', // Ensure media is an array with at least one item
            'media.*' => 'required|string', // Each media item must be a string
        ]);
        $gallery->update([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'hashtag' => $request->hashtag,
            'status' => $request->status,
            'media' => json_encode($validatedData['media']), // Convert media collection to JSON string for storage
        ]);
        $updatedGallery = Gallery::findOrFail($id);
        $updatedGallery->media = json_decode($updatedGallery->media, true);
        return response()->json(['status' => 'success', 'gallery' => $updatedGallery], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => "Server error"], 404);
    }
}

/**
 * @OA\Get(
 *     path="/api/galleries",
 *     summary="List Galleries",
 *     tags={"Gallery"},
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
            $galleries = Gallery::where('leader_id', Auth::user()->id)
                                    ->whereIn('status', ['published', 'unpublished'])
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($perPage);
        } elseif ($request->userType == "Party") {
            $galleries = Gallery::where('party_id', $request->party_id)
                                ->whereIn('status', ['published', 'unpublished'])
                                ->orderBy('created_at', 'desc')
                                ->paginate($perPage);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Invalid user type'], 400);
        }
        
        foreach ($galleries as $gallery) {
            $gallery->media = json_decode($gallery->media);
            $gallery->currentPage = $galleries->currentPage();
        }
        $currentPage = $galleries->currentPage();
        $lastPage = $galleries->lastPage();
        return response()->json(['status' => 'success', 'galleries' => $galleries, 'current_page' => $currentPage,
        'last_page' => $lastPage], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
}

/**
 * @OA\Put(
 *     path="/api/galleries/{id}/status",
 *     summary="Update Gallery Status by ID",
 *     tags={"Gallery"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the gallery to update",
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
 *             @OA\Property(property="status", type="string", example="published/unpublished/archived", description="Gallery status (unpublished, published, or archived)"),
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
         $gallery = Gallery::findOrFail($id);
         $userType = $request->userType;
         
         if($userType == "Leader"){
            $archive_by = Auth::user()->id;
            $archive_by_type = $userType; 
         }else {
            $archive_by = $request->party_id;
            $archive_by_type = $userType;
        }
         $gallery->update(
            [
                'status' => $validatedData['status'],
                'archive_by' => $archive_by,
                'archive_by_type' => $archive_by_type,
        ]);
         return response()->json(['status' => 'success', 'gallery' => $gallery], 200);
     } catch (\Exception $e) {
         LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
     }
 }
 
/**
 * @OA\Get(
 *     path="/api/galleries/update/{id}",
 *     summary="Get Gallery by ID",
 *     tags={"Gallery"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID of the gallery to update",
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



 public function update(Request $request, $id)
 {
     try {
         $gallery = Gallery::findOrFail($id);
         if($gallery){
            $media = json_decode($gallery->media, true);
            $gallery->media = $media;
            return response()->json(['status' => 'success', 'gallery' => $gallery], 200);
         }else {
            return response()->json(['status' => 'failed', 'gallery' => "not found gallery"], 404);
         }
         
     } catch (\Exception $e) {
        return $e->getMessage();
         LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
         return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
     }
 }
}
