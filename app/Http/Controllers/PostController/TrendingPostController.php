<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\FetchAllPost;
use App\Helpers\FetchTrendingPost;
use App\Helpers\OptimizeFetchPost;
use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\MasterHashTag;
use App\Helpers\HttpHelper;
use Auth;
use Illuminate\Http\Request;
use DB;

class TrendingPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/trendingpost",
     *     summary="Fetch all Trending Post",
     *     tags={"FetchPost"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found"
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $userId = $partyId ? $partyId : Auth::user()->id;
            $currentPage = request('page', 1);
            $trendingPosts = HttpHelper::trendingPosts();
            // Check if the response is not null and return it
            // if ($trendingPosts !== null) {
            //     return response()->json($trendingPosts, 200);
            // } else {
            //     return response()->json(['status' => 'error', 'message' => "Failed to fetch trending posts"], 500);
            // }
    //         $sqlQuery = "
    // SELECT id, \"postTitle\",
    //        SUM(\"likesCount\") AS totalLikes,
    //        SUM(\"commentsCount\") AS totalComments,
    //        SUM(\"shareCount\") AS totalShares
    // FROM (
    //     SELECT id, \"postTitle\", \"likesCount\", \"commentsCount\", \"shareCount\" FROM post_by_leaders WHERE \"isPublished\" = true
    //     UNION ALL
    //     SELECT id, \"postTitle\", \"likesCount\", \"commentsCount\", \"shareCount\" FROM post_by_citizens WHERE \"isPublished\" = true
    //     UNION ALL
    //     SELECT id, \"postTitle\", \"likesCount\", \"commentsCount\", \"shareCount\" FROM post_by_parties WHERE \"isPublished\" = true
    // ) AS combined_posts
    // GROUP BY id, \"postTitle\"
    // HAVING SUM(\"likesCount\") + SUM(\"commentsCount\") + SUM(\"shareCount\") > 0
    // ORDER BY SUM(\"likesCount\") + SUM(\"commentsCount\") + SUM(\"shareCount\") DESC
    // LIMIT 10";
    //         $list = DB::select($sqlQuery);
            $ids = collect($trendingPosts)->pluck('id')->toArray();
            // return $ids;
            // $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, $ids, null, null, $ids);
            $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, $ids, null, null,$ids);
            // $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, $ids, null, null);
            return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $allPost], 200);
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
