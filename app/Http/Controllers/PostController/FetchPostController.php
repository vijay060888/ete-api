<?php

namespace App\Http\Controllers\PostController;

use Crypt;
use App\Models\User;
use App\Helpers\Action;
use App\Models\FactBuster;
use App\Models\ReportPost;
use App\Models\PostByParty;
use App\Helpers\LogActivity;
use App\Models\PostByLeader;
use Illuminate\Http\Request;
use App\Helpers\FetchAllPost;
use App\Models\PostByCitizen;
use App\Helpers\EncryptionHelper;
use App\Helpers\OptimizeFetchPost;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Helpers\OptimizeFetchPostTest;
use App\Http\Controllers\SimilarAssembly\SimilarAssemblyController;

class FetchPostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/fetchallPost",
     *     summary="Fetch all Post",
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
            $partyId = $request->input('partyId');
            $userId = Auth::user()->id;
            $currentPage = request('page', 1);
            // $allPost = FetchAllPost::getAllPost($currentPage,$partyId,null,null,null,null,null);
            $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, null, null, null);
            return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $allPost], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    // 'suggestions' => [
    //     'parties' => SimilarAssemblyController::similarpartiesByUser($currentPage),
    //     'assembly' => SimilarAssemblyController::similarassembliesByUser($userId,$currentPage) ?? [],
    //     'leaders' => SimilarAssemblyController::similarLeadersByUser($userId, $currentPage),
    //     'loksabha' => []
    // ]
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/fetchallPostTest",
     *     summary="Fetch all Post test",
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


    public function fetchallPostTest(Request $request)
    {
        try {
            $partyId = $request->input('partyId');
            $userId = Auth::user()->id;
            $currentPage = request('page', 1);
            // $allPost = FetchAllPost::getAllPost($currentPage,$partyId,null,null,null,null,null);
            $allPost = OptimizeFetchPostTest::getAllPost($currentPage, $partyId, null, null, null, null, null);
            return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $allPost], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/reportPost",
     *     summary="Report Post",
     *     tags={"FetchPost"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"storyContent","authorType","postId"},
     *         @OA\Property(property="authorType", type="string", example="postByType"),
     *        @OA\Property(property="reportText", type="string", example="reportText"),
     *        @OA\Property(property="postId", type="string", example="postId")
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
    public function reportPost(Request $request)
    {
        try {
            $postId = $request->postId;
            $postType = $request->authorType;
            $user = Auth::user();
            $reportText = $request->reportText;

            $reportPost = [
                'id' => DB::raw('gen_random_uuid()'),
                "postByType" => $postType,
                "reportText" => $reportText,
                "reportedBy" => $user->id,
                "createdBy" => $user->id,
                'createdAt' => now(),
                'updatedAt' => now(),
            ];

            $conditions = [
                "postId" => $postId,
                "reportedBy" => $user->id,
            ];
            $checkifExists = ReportPost::where('postId', $postId)->where('reportedBy', $user->id)->exists();
            if ($checkifExists) {
                return response()->json(['status' => 'error', 'message' => "Your Report is already submitted"], 400);
            }
            $template = Action::getTemplate('report post');
            $adminUser = User::role('Super Admin')->first();
            $fullName = $adminUser->getFullName();
            $url = env('APP_URL');
            $postUrl = $url . "/api/fetchallPost/" . $postId;
            $postReportedBy = $user->getFullName();
            if ($template['type'] == "template") {
                foreach ($template['data'] as $d) {
                    $content = str_replace(["{adminName}", "{postUrl}", "{postReportedBy}"], [$fullName, $postUrl, $postReportedBy], $d->content);
                    $mail['content'] = $content;
                    $mail['email'] = $adminUser->email;
                    $mail['subject'] = $d->subject;
                    $mail['fileName'] = "template";
                    $mail['cc'] = '';
                    if ($d->cc != null) {
                        $mail['cc'] = $d->cc;
                    }
                }
                // Action::sendEmail($mail);
            }

            ReportPost::updateOrInsert($conditions, $reportPost);
            $countReport = ReportPost::where('postId', $postId)->count();
            $post = PostByLeader::find($postId) ?? PostByParty::find($postId) ?? PostByCitizen::find($postId);
            
            if ($post && $countReport >= 5) {
                $post->isPublished = false;
                $post->save();
            }
            return response()->json(['status' => 'success', 'message' => "Post Reported"], 200);

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
    /**
     * @OA\Get(
     *     path="/api/fetchallPost/{id}",
     *     summary="Fetch Post by id",
     *     tags={"FetchPost"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
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
    public function show(Request $request, string $id)
    {
        try {
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);
        $postId = $id;
        $postIdArray = [$postId];
        $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, $postIdArray, null, null);

        return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $allPost], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
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

    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Delete(
     *     path="/api/fetchallPost/{postId}",
     *     summary="Delete post by postid",
     *     tags={"FetchPost"},
     *     @OA\Parameter(
     *         name="postId",
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
            $postId = $id;
            $postType = $request->authorType;

            $postByModelClass = ($postType == 'Leader') ? PostByLeader::class :
            (($postType == 'Citizen') ? PostByCitizen::class :
                (($postType == 'Party') ? PostByParty::class : null));

            $post = $postByModelClass::where('id', $postId)->first();
            $post->delete();
            return response()->json(['status' => 'success', 'message' => 'Post Deleted Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/getComplaintPost",
     *     summary="Fetch all ComplaintPost",
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
    public function getComplaintPost(Request $request)
    {
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);
        $filterByPost = 'Complaint';
        // $usersId = Auth::user()->id;
        // $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        // $encryptedData = Crypt::encrypt([$usersId, $rolename]);
        $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, null, null,  $filterByPost);
        // $allPost = FetchAllPost::getAllPost($currentPage,$partyId,null,null,null,$encryptedData,$filterByPost);
        return response()->json(['status' => 'success', 'message' => "All Complaint Post Result", "result" => $allPost], 200);

    }


    // public function getComplaintPost(Request $request)
    // {
    //     try {
    //         $currentPage = request('page', 1);
    //         $filterByPost = 'Complaint';
    //         $userId = Auth::user()->id;
    //         $roleName = Auth::user()->getRoleNames()[0];
    //         $encryptedData = Crypt::encrypt([$userId, $roleName]);
    //         $allPost = FetchAllPost::getAllPost($currentPage, null, null, null, null, $encryptedData, $filterByPost);
            
    //         return response()->json(['status' => 'success', 'message' => "All Complaint Post Result", "result" => $allPost], 200);
    //     } catch (\Exception $e) {
    //         \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
    //         return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
    //     }
    // }



    /**
     * @OA\Get(
     *     path="/api/getIdeaPost",
     *     summary="Fetch all getIdeaPost",
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
    public function getIdeaPost(Request $request)
    {
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);
        $filterByPost = 'Idea';
        // $usersId = Auth::user()->id;
        // $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        // $usersId = $partyId !== null ? $partyId : Auth::user()->id;
        // $encryptedData = Crypt::encrypt([$usersId, $rolename]);
        // $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, null, $encryptedData, $filterByPost);
        $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, null, null,  $filterByPost);
        return response()->json(['status' => 'success', 'message' => "All Idea Post Result", "result" => $allPost], 200);

    }


    /**
     * @OA\Get(
     *     path="/api/getpollsPost",
     *     summary="Fetch all getIdeaPost",
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
    public function getpollsPost(Request $request)
    {
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);
        $filterByPost = 'Polls';
        // $usersId = Auth::user()->id;
        // $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        // $usersId = $partyId !== null ? $partyId : Auth::user()->id;
        // $encryptedData = Crypt::encrypt([$usersId, $rolename]);
        // $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, null, $encryptedData, $filterByPost);
        $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, null, null,  $filterByPost);
        return response()->json(['status' => 'success', 'message' => "All Polls Post Result", "result" => $allPost], 200);

    }


    /**
     * @OA\Get(
     *     path="/api/geteventsPost",
     *     summary="Fetch all getIdeaPost",
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
    public function geteventsPost(Request $request)
    {
        $partyId = $request->input('partyId');
        $currentPage = request('page', 1);
        $filterByPost = 'Events';
        // $usersId = Auth::user()->id;
        // $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        // $usersId = $partyId !== null ? $partyId : Auth::user()->id;
        // $encryptedData = Crypt::encrypt([$usersId, $rolename]);
        // $allPost = FetchAllPost::getAllPost($currentPage, $partyId, null, null, null, $encryptedData, $filterByPost);
        $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, null, null, null,  $filterByPost);
        return response()->json(['status' => 'success', 'message' => "All Events Post Result", "result" => $allPost], 200);

    }





    public function sharedPostOpen($id)
    {
        $postByType = request('postByType');
        $postId = EncryptionHelper::decryptString($id);
        if ($postByType == 'Leaders' || $postByType=='Leader' ) {
            $postDetails = PostByLeader::where('id', $postId)->with([
                'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
            ])->first();
            return view('openSharedPost', [
                'postId' => $postId,
                'postTitle' => $postDetails->postTitle,
                'postDescription' => $postDetails->postDescription,
                'postImage' => $postDetails->postByLeaderMetas[0]->imageUrl1,
            ]);
        }

        if ($postByType == 'Citizen') {
            $postDetails = PostByCitizen::where('id', $postId)->with([
                'postByCitizenMetas:id,postByCitizenId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByCitizenDetails:id,postByCitizenId,pollOption,optionCount',
                'pollsByCitizenDetails.pollsByCitizenVotes:id,pollsByCitizenDetailsId',
                'user',
                'user.userDetails'
            ])->first();
            return view('openSharedPost', [
                'postId' => $postId,
                'postTitle' => $postDetails->postTitle,
                'postDescription' => $postDetails->postDescription,
                'postImage' => $postDetails->postByCitizenMetas[0]->imageUrl1,
            ]);
        }

        if ($postByType == 'Party') {
            $postDetails = PostByParty::where('id', $postId)->with([
                'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
                'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
                'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
            ])->first();

            return view('openSharedPost', [
                'postId' => $postId,
                'postTitle' => $postDetails->postTitle,
                'postDescription' => $postDetails->postDescription,
                'postImage' => $postDetails->postByPartyMetas[0]->imageUrl1,
            ]);
        }

        if ($postByType == 'Super-Admin') {
            $getFactBuster = FactBuster::where("factId", $postId)->first();
            $getAdminMedia = $this->separateMedia($getFactBuster->attachments);
            return view('openSharedPost', [
                'postId' => $postId,
                'postTitle' => $getFactBuster->title,
                'postDescription' => $getFactBuster->description,
                'postImage' => $getAdminMedia['otherMedia'][0] ?? null,
            ]);
        }
    }

    function separateMedia($mediaString) {
        $mediaArr = explode(',', $mediaString);
    
        $attachments = [];
        $otherMedia = [];
        foreach ($mediaArr as $mediaUrl) {
            if (pathinfo($mediaUrl, PATHINFO_EXTENSION) === 'pdf') {
                $attachments[] = trim($mediaUrl);
            } else {
                $otherMedia[] = trim($mediaUrl);
            }
        }
        return ['attachments' => $attachments, 'otherMedia' => $otherMedia];
    }

}