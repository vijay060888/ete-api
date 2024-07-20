<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\Category;
use Auth;
use App\Helpers\LogActivity;

class VendorController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/vendors",
     *     summary="Create a new vendor",
     *     tags={"Vendor"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Vendor data",
     *         @OA\JsonContent(
     *             @OA\Property(property="postType", type="string", example="Leader/Party"),
     *             @OA\Property(property="leader_id", type="string", example="leader_id"),
     *             @OA\Property(property="party_id", type="string", example="party_id"),
     *             @OA\Property(property="category", type="string", example="Category"),
     *             @OA\Property(property="name", type="string", example="Vendor Name"),
     *             @OA\Property(property="gst", type="string", example="GST Number"),
     *             @OA\Property(property="pan", type="string", example="PAN Number"),
     *             @OA\Property(property="address", type="string", example="Vendor Address"),
     *             @OA\Property(property="services", type="string", example="Vendor Services"),
     *             @OA\Property(property="website", type="string", example="https://vendor.com"),
     *             @OA\Property(property="email", type="string", example="vendor@example.com"),
     *             @OA\Property(property="phone", type="string", example="1234567890"),
     *             @OA\Property(property="hashtags", type="string", example="vendor, services"),
     *             @OA\Property(property="media", type="string", example="media, services"),
     *         ),
     *     ),
     *    @OA\Response(
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
        try {

            $validatedData = $request->validate([
                'category' => 'nullable|string',
                'name' => 'nullable|string',
                'gst' => 'nullable|string',
                'pan' => 'nullable|string',
                'address' => 'nullable|string',
                'services' => 'nullable|string',
                'website' => 'nullable|string|url',
                'email' => 'nullable|string|email',
                'phone' => 'nullable|string',
                'hashtags' => 'nullable|string',
            ]);
            $category = $validatedData['category'];
            $name = $validatedData['name'];
            $gst = $validatedData['gst'];
            $pan = $validatedData['pan'];
            $address = $validatedData['address'];
            $services = $validatedData['services'];
            $website = $validatedData['website'];
            $email = $validatedData['email'];
            $phone = $validatedData['phone'];
            $hashtags = $validatedData['hashtags'];
            $party_id = null;
            $leader_id = null;
            $postType = $request->postType;
            $media = $request->media;
            if($postType == "Leader"){
                $leader_id = Auth::user()->id;
                $createdBy = Auth::user()->id;
                
            }else {
                $party_id = $request->party_id;
                $createdBy = $request->party_id;
            }
    
            $vendor = Vendor::create([
                'leaderId' => $leader_id,
                'partyId' => $party_id,
                'category' => $category,
                'name' => $name,
                'gst' => $gst,
                'pan' => $pan,
                'address' => $address,
                'services' => $services,
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
                'hashTags' => $hashtags,
                'media' => $media,
                'createdBy' => $createdBy,
                'createdAt' => now(),
                'updatedBy' => $createdBy,
            ]);
            $vendor = Vendor::where('id', $vendor->id)->first();
            return response()->json(['status' => 'success','message' => 'Vendor created successfully', 'vendor' => $vendor], 201);
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
        
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/vendors/{id}",
     *     summary="Update an existing vendor",
     *     tags={"Vendor"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the Vendor to update",
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
     *             @OA\Property(property="postType", type="string", example="Leader/Party"),
     *             @OA\Property(property="leader_id", type="string", example="leader_id"),
     *             @OA\Property(property="party_id", type="string", example="party_id"),
     *             @OA\Property(property="category", type="string", example="Category"),
 *                 @OA\Property(property="name", type="string", example="Vendor Name"),
 *                 @OA\Property(property="gst", type="string", example="GST Number"),
 *                 @OA\Property(property="pan", type="string", example="PAN Number"),
 *                 @OA\Property(property="address", type="string", example="Vendor Address"),
 *                 @OA\Property(property="services", type="string", example="Vendor Services"),
 *                 @OA\Property(property="website", type="string", example="https://vendor.com"),
 *                 @OA\Property(property="email", type="string", example="vendor@example.com"),
 *                 @OA\Property(property="phone", type="string", example="1234567890"),
 *                 @OA\Property(property="hashtags", type="string", example="vendor, services"),
 *                 @OA\Property(property="media", type="string", example="media, services"),
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
public function edit(Request $request, $id)
{
    try {
        $validatedData = $request->validate([
            'category' => 'nullable|string',
            'name' => 'nullable|string',
            'gst' => 'nullable|string',
            'pan' => 'nullable|string',
            'address' => 'nullable|string',
            'services' => 'nullable|string',
            'website' => 'nullable|string|url',
            'email' => 'nullable|string|email',
            'phone' => 'nullable|string',
            'hashtags' => 'nullable|string',
        ]);
        $category = $validatedData['category'];
            $name = $validatedData['name'];
            $gst = $validatedData['gst'];
            $pan = $validatedData['pan'];
            $address = $validatedData['address'];
            $services = $validatedData['services'];
            $website = $validatedData['website'];
            $email = $validatedData['email'];
            $phone = $validatedData['phone'];
            $hashtags = $validatedData['hashtags'];
            $party_id = null;
            $leader_id = null;
            $postType = $request->postType;
            $media = $request->media;
            if($postType == "Leader"){
                $leader_id = Auth::user()->id;
                $createdBy = Auth::user()->id;
                
            }else {
                $party_id = $request->party_id;
                $createdBy = $request->party_id;
            }
        $vendor = Vendor::findOrFail($id);
        $vendor->update([
            'leaderId' => $leader_id,
                'partyId' => $party_id,
                'category' => $category,
                'name' => $name,
                'gst' => $gst,
                'pan' => $pan,
                'address' => $address,
                'services' => $services,
                'website' => $website,
                'email' => $email,
                'phone' => $phone,
                'hashTags' => $hashtags,
                'media' => $media,
                'updatedAt' => now(),
                'updatedBy' => $createdBy,
        ]);

        $vendor->update($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Vendor updated successfully',
            'vendor' => $vendor
        ], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
}

 /**
     * @OA\Get(
     *     path="/api/vendors",
     *     summary="List Vendors",
     *     tags={"Vendor"},
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
             if ($request->userType == "Leader") {
                 $vendor = Vendor::where('leaderId', Auth::user()->id)
                                         ->get();
             } elseif ($request->userType == "Party") {
                 $vendor = Vendor::where('partyId', $request->party_id)
                                     ->get();
             } else {
                 return response()->json(['status' => 'error', 'message' => 'Invalid user type'], 400);
             }
             return response()->json(['status' => 'success', 'achievements' => $vendor], 200);
         } catch (\Exception $e) {
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
         }
     }

     /**
     * @OA\Delete(
     *     path="/api/vendors/{id}",
     *     summary="Delete an existing vendor",
     *     tags={"Vendor"},
     *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="UUID of the vendor to delete",
    *         required=true,
    *         @OA\Schema(
    *             type="string",
    *             format="uuid"
    *         )
    *     ),
     *    @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="No Content",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function delete($id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Vendor deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/category",
     *     summary="List category",
     *     tags={"Vendor"},
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


     public function categoryList(Request $request)
     {
         try {
            $category = Category::get();
            return response()->json(['status' => 'success', 'Category' => $category], 200);
         } catch (\Exception $e) {
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
         }
     }


}
