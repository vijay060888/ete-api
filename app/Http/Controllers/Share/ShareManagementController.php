<?php

namespace App\Http\Controllers\Share;

use App\Http\Controllers\Controller;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use Illuminate\Http\Request;

class ShareManagementController extends Controller
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
     *     path="/api/share",
     *     summary="Increase share count",
     *     tags={"Share"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"postId","authorType"},
     *         @OA\Property(property="postId", type="string", example="postId"),
     *          @OA\Property(property="authorType", type="string", example="authorType"),
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
        $postId = $request->postId;
        $authorType = $request->authorType;
        $postByModelClass = ($authorType == 'Leader') ? PostByLeader::class :
            (($authorType == 'Citizen') ? PostByCitizen::class :
                (($authorType == 'Party') ? PostByParty::class : null));
        $postByModel = new $postByModelClass;
        $postDetails = $postByModel::find($postId);
        $postDetails->shareCount++;
        $postDetails->save();
        return response()->json(['status' => 'error','message' => 'Shared'],404);
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
