<?php

namespace App\Http\Controllers\ManifestoTracker;

use App\Helpers\EncryptionHelper;
use App\Http\Controllers\Controller;
use App\Models\ElectionHistory;
use App\Models\ElectionType;
use App\Models\LogActivity;
use App\Models\Manifesto;
use App\Models\ManifestoComments;
use App\Models\ManifestoCommentsReply;
use App\Models\ManifestoLike;
use App\Models\ManifestoPromises;
use App\Models\ManifestoPromisesComments;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use App\Models\UserAddress;
// use Auth;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class ManifestoTrackerManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */


    public function index(Request $request)
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
     * @OA\POST(
     *     path="/api/manifesto",
     *     summary="Manifesto Management",
     *     tags={"Manifesto Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request parameters",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="electionType",
     *                 description="Election Type",
     *                 type="string",
     *                 example="LokSabha"
     *             ),
     *             @OA\Property(
     *                 property="stateId",
     *                 description="ID of the state",
     *                 type="string",
     *                 example=2
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
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
    public function store(Request $request)
{
    $electionTypeName = $request->get('electionType');
    if (!in_array($electionTypeName, ["Assembly", "LokSabha"])) {
        return response()->json(['status' => 'error', 'message' => 'Invalid Election Type'], 400);
    }

    $stateId = $request->get('stateId');
    $resultCollection = [];
    $partyId = request('partyId');
    $currentPage = request('page', 1);
    $electionData = ElectionType::leftJoin('election_histories', 'election_histories.electionTypeId', 'election_types.id')
                        ->where('election_types.electionName', $electionTypeName)->where('election_types.electionStatus', 'Completed');
    
    if ($electionTypeName == 'Assembly' && $stateId) {
        $electionData = $electionData->where('election_types.stateId', $stateId);
    }
    $electionData = $electionData->orderBy('election_histories.electionHistoryYear', 'DESC')->first();

    if(!$electionData) {
        return response()->json(['status' => 'error', 'message' => 'No Manifesto Record found'], 400);
    }


    $userStateId = UserAddress::leftJoin('states', 'states.name','user_addresses.state')
                    ->where('user_addresses.userId', Auth::user()->id)->pluck('states.id')->first();
    if ($electionTypeName == 'Assembly' && !$stateId) {
        return response()->json(['status' => 'error', 'message' => 'Kindly provide state to proceed.'], 400);
    }

    $canReact = ($electionTypeName == 'LokSabha') ? True : (($electionTypeName == 'Assembly' && $stateId && $stateId == $userStateId) ? true : false);

    $currentManifesto = Manifesto::where('electionHistoryId', $electionData->id)->with(['party', 'electionHistory'])->first();
    if (!$currentManifesto) {
        return response()->json(['status' => 'error', 'message' => 'No current manifesto found'], 400);   
    }
    $manifestoDetails = [
        "electionName" => $electionData->electionName,
        "currentRullingParty" => $currentManifesto->party->name,
        "currentRullingPartyLogo" => $currentManifesto->party->logo,
        "electionTerm" => $electionData->electionTerm,
        "manifestoDownloadLink" => $currentManifesto->manifestoFile,
    ];

    $resultCollection["manifestoDetails"] = $manifestoDetails;

    $maniFestoPromises = ManifestoPromises::where('manifestoId', $currentManifesto->id)
        ->with('manifesto.party', 'manifesto.electionHistory')
        ->orderBy('createdAt','desc')
        ->get();

    $manifestoPromisesCollection = $maniFestoPromises->map(function ($item) use ($currentPage, $canReact, $partyId) {
        $likes = new ManifestoLike();
        $likeByType = ($partyId !== null) ? 'Party' : Auth::user()->getRoleNames()[0];
        $likeById = ($partyId !== null) ? $partyId : Auth::user()->id;
        $likeType = $likes->hasLiked($likeByType, $likeById, $item->id);
        $isLiked = $likeType ? true : false;
        $url = env('APP_URL');
        $encryptedPostId = EncryptionHelper::encryptString($item->id);
        $manifestoURL = $url . '/sharedManifesto/' . $encryptedPostId;
        $electionName = ElectionType::where('id', $item->manifesto->electionHistory->electionTypeId)->first();

        return [
            'manifestoSharedURL' => $manifestoURL,
            'manifestoPromiseId' => $item->id,
            'partyName' => $item->manifesto->party->name,
            'partyLogo' => $item->manifesto->party->logo,
            'electioNameYear' => 'Manifesto' . ' ' . $electionName->electionTypeDescriptionBrief,
            'createdAt' => $item->createdAt->diffForHumans(),
            'promiseDepartment' => $item->manifestoPromisesDepartment,
            'promiseTitle' => $item->manifestoPromisesPromise,
            'promiseDepartmentUrl' => $item->manifestodepturl,
            'manifestoDescriptions' => $item->manifestoPromisesDescriptions,
            'likesCount' => $item->likesCount,
            'isLiked' => $isLiked,
            'commentsCount' => $item->commentsCount,
            'likedType' => $likeType,
            'currentPage' => $currentPage,
            'canReact' => $canReact
        ];
    });

    $desiredTotal = $manifestoPromisesCollection->count();
    $perPage = 5;
    // $currentPage = 1;
    $currentPage = request()->query('page', 1); // Get the current page from the request query, default to 1 if not provided
    $slicedCollection = $manifestoPromisesCollection->slice(($currentPage - 1) * $perPage, $perPage); // Slice the collection based on the current page and per page count
    $promisesArray = $slicedCollection->values()->all();
    $list = new LengthAwarePaginator($promisesArray, $desiredTotal, $perPage, $currentPage, [
        'path' => request()->url(),
        'query' => request()->query(),
    ]);

    $resultCollection["promises"] = $list->toArray();

    return response()->json(['status' => 'success', 'message' => 'Manifesto Promises', 'result' => $resultCollection], 200);
}





    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/manifesto/{id}",
     *     summary="Fetch manifestoPromises by id",
     *     tags={"Manifesto Management"},
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
    public function show(string $id)
    {

        $manifestoPromises = ManifestoPromises::where('id', $id)->with('manifesto.party', 'manifesto.electionHistory')->first();
        $electionType = ElectionType::where('id', $manifestoPromises->manifesto->electionHistory->electionTypeId)->first();
        $dateTime = new \DateTime($manifestoPromises->createdAt);
        $formattedDateTime = $dateTime->format('d-M Y h:i A');

        $promisesDetails = [
            "electionName" => $electionType->electionName,
            "manifestoTitle" => $manifestoPromises->manifestoPromisesPromise,
            "currentRullingParty" => $manifestoPromises->manifesto->party->name,
            "currentRullingPartyLogo" => $manifestoPromises->manifesto->party->logo,
            "manifestoPromiseStatus" => $manifestoPromises->manifestoPromisesIdStatus,
            "manifestoPromisesDepartment" => $manifestoPromises->manifestoPromisesDepartment,
            'manifestoPromisesDescriptions' => $manifestoPromises->manifestoPromisesDescriptions,
            'manifestoShortDescriptions' => $manifestoPromises->manifestoShortDescriptions,
            'createdAt' => $formattedDateTime
        ];
        $resultCollection["manifestoDetails"] = $promisesDetails;

        $manifestoComments = ManifestoPromisesComments::where('manifestoPromisesId', $id)
            ->get();
        if ($manifestoComments) {
            $commentsCollection = $manifestoComments->map(function ($comment) {
                return [
                    'manifestoPromisesCommentHeader' => $comment->manifestoPromisesCommentHeader,
                    'manifestoPromisesCommentText' => $comment->manifestoPromisesCommentText,
                    'commentsDepartment' => $comment->commentsDepartment,
                    'commentAttachment' => $comment->commentAttachment,
                    'departmentURL' => $comment->departmentURL,
                ];
            });
        }
        $desiredTotal = $commentsCollection->count();
        $perPage = 5;
        $currentPage = 1;
        $list = new LengthAwarePaginator($commentsCollection, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
        $resultCollection["promiseComments"] = $list->toArray();

        return response()->json(['status' => 'success', 'message' => 'Manifesto Promises Details', 'result' => $resultCollection], 200);


    }

    /**
     * @OA\POST(
     *     path="/api/addReaction",
     *     summary="Add Reaction",
     *     tags={"Manifesto Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Request parameters",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="manifestoId",
     *                 description="Manifesto Id",
     *                 type="string",
     *                 example="manifestoUUId"
     *             ),
     *               @OA\Property(
     *                 property="likedType",
     *                 description="Liked Type",
     *                 type="string",
     *                 example="like/sad"
     *             ),
     *             @OA\Property(
     *                 property="partyId",
     *                 description="Party Id",
     *                 type="string",
     *                 example=2
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
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
    public function addReaction(Request $request)
    {

        try {
            $manifestoId = $request->manifestoId;
            $likedType = $request->likedType;
            $partyId = $request->partyId;
            $userType = empty($partyId) ? Auth::user()->getRoleNames()[0] : "Party";
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $manifestoByModelClass = ManifestoPromises::class;
            if ($manifestoByModelClass) {
                $like = ManifestoLike::where([
                    'manifestoId' => $manifestoId,
                    'likeById' => $userId,
                ])->first();

                if (in_array($likedType, ['like', 'care', 'sad', 'appreciate', 'unlike'])) {
                    if (!$like) {
                        $postByModel = new $manifestoByModelClass;
                        $postDetails = $postByModel::find($manifestoId);
                        $postDetails->likesCount++;
                        $postDetails->save();
                    }
                    ManifestoLike::updateOrInsert(
                        [
                            'manifestoId' => $manifestoId,
                            'likeById' => $userId,
                        ],
                        [
                            'id' => \DB::raw('gen_random_uuid()'),
                            'likeType' => $likedType,
                            'likeByType' => $userType,
                            'createdBy' => Auth::user()->id,
                            'updatedBy' => Auth::user()->id,
                            'createdAt' => now(),
                            'updatedAt' => now(),
                        ]
                    );

                } elseif ($likedType == 'delete') {
                    if ($like) {
                        $postByModel = new $manifestoByModelClass;
                        $postDetails = $postByModel::find($manifestoId);
                        $postDetails->likesCount--;
                        $postDetails->save();
                        $like->delete();
                    }
                }
                return response()->json(['status' => 'success', 'message' => 'Likes Updated',], 200);
            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }




    }


    /**
     * @OA\Get(
     *     path="/api/manifestoLikesdetails/{postId}",
     *     summary="Fetch likes details by manifestoPromiseId",
     *     tags={"Manifesto Management"},
     *     @OA\Parameter(
     *         name="postId",
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
    public function manifestoLikesdetails($id)
    {
        try {
            $likes = ManifestoLike::where('manifestoId', $id)->orderBy('createdAt', 'desc')->get();
            $likersDetails = [];
            foreach ($likes as $like) {
                $userType = $like->likeByType;
                $userId = $like->likeById;

                $userDetails = null;
                $profileImage = null;
                $name = '';
                switch ($userType) {
                    case 'Leader':
                    case 'Citizen':
                        $userDetails = User::with('userDetails')->find($userId);
                        if ($userDetails) {
                           $profileImage = isset($userDetails->userDetails->profileImage) ? $userDetails->userDetails->profileImage : null;
                            $name = $userDetails->firstName . ' ' . $userDetails->lastName;
                        }
                        break;

                    case 'Party':
                        $userDetails = Party::find($userId);
                        if ($userDetails) {
                            $profileImage = $userDetails->logo;
                            $name = $userDetails->name;
                        }
                        break;
                }

                if ($userDetails) {
                    $likersDetails[] = [
                        'userId' => $userDetails->id,
                        'name' => $name,
                        'likeByType' => $like->likeByType,
                        'likeType' => $like->likeType,
                        'profileImage' => $profileImage,
                    ];

                }
            }
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $page = request('page', 1);

            $totalLikes = count($likersDetails);
            $likersDetailsPaginated = array_slice($likersDetails, ($page - 1) * $perPage, $perPage);

            $list = new LengthAwarePaginator($likersDetailsPaginated, $totalLikes, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'All Likes details', 'result' => $list], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }

    }


    /**
     * @OA\Post(
     *     path="/api/addComments",
     *     summary="Add new comment to post",
     *     tags={"Manifesto Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"manifestoPromiseId", "content","partyId"},
     *             @OA\Property(property="authorType", type="string", example="Leader/Citizen"),
     *             @OA\Property(property="manifestopromiseId", type="string", example="manifestopromiseId"),
     *             @OA\Property(property="content", type="string", example="comments"),
     *            @OA\Property(property="partyId", type="string", example="partyId")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found "
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     **/
    public function addComments(Request $request)
    {
        try {
            $manifestoPromiseId = $request->manifestopromiseId;
            $partyId = $request->partyId;
            $authorType = $request->authorType;
            $content = $request->content;
            $userId = $partyId !== null ? $partyId : Auth::user()->id;
            $authorType = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];

            $comments = [
                'manifestoPromiseId' => $manifestoPromiseId,
                'commentById' => $userId,
                'authorType' => $authorType,
                'content' => $content,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
            ];

            // return ($comments);

            ManifestoComments::create($comments);
            $manifestoPromise = ManifestoPromises::find($manifestoPromiseId);

            // return ($manifestoPromise);

            if (!$manifestoPromise) {
                return response()->json(['status' => 'error', 'message' => 'Manifesto promise not found'], 404);
            }
            
            $manifestoPromise->commentsCount++;
            $manifestoPromise->save();

            return response()->json(['status' => 'success', 'message' => 'Comments added'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/showManifestoComments/{manifestopromiseId}",
     *     summary="Fetch all Manifesto Comments",
     *     tags={"Manifesto Management"},
     *     @OA\Parameter(
     *         name="manifestopromiseId",
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
    public function showManifestoComments($id)
    {
        try {
            $partyId = request('partyId');
            $userId = $partyId ?? Auth::user()->id;
            $comments = ManifestoComments::where('manifestoPromiseId', $id)->orderBy('createdAt', 'desc')->get();
            $commentsDetails = [];
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $page = request('page', 1);
            foreach ($comments as $comment) {
                $userType = $comment->authorType;
                $commentById = $comment->commentById;
                $isEditable = ($commentById == $userId);
                $userDetails = null;
                $name = '';
                $createdAtDifference = $comment->createdAt->diffForHumans();
                switch ($userType) {
                    case 'Leader':
                    case 'Citizen':
                        $userDetails = User::with(['userDetails', 'userAddress'])->find($commentById);

                        if ($userDetails) {
                            $name = $userDetails->firstName . ' ' . $userDetails->lastName;
                            $profileImage = $userDetails->userDetails->profileImage;
                            if ($userDetails->cityTown == '' && $userDetails->district == '') {
                                $address = $userDetails->userAddress->cityTown . " " . $userDetails->userAddress->district;
                            } else {
                                $address = $userDetails->cityTown . " " . $userDetails->district;
                            }
                        }
                        break;

                    case 'Party':
                        $userDetails = Party::find($commentById);
                        if ($userDetails) {
                            $name = $userDetails->name;
                            $address = "";
                            $profileImage = $userDetails->logo;
                        }
                        break;
                }

                if ($userDetails) {
                    $commentsDetails[] = [
                        'content' => $comment->content,
                        'name' => $name,
                        'userId' => $userDetails->id,
                        'address' => $address,
                        'profileImage' => $profileImage,
                        'commentsByType' => $comment->authorType,
                        'commentId' => $comment->id,
                        'isEditable' => $isEditable,
                        'repliesCount' => $comment->repliesCount,
                        'createdAt' => $createdAtDifference,
                        'currentPage' => $page
                    ];
                }
            }


            

            $totalComments = count($commentsDetails);
            $paginatedData = collect($commentsDetails)->forPage($page, $perPage)->values();

            $list = new LengthAwarePaginator($paginatedData, $totalComments, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return response()->json(['status' => 'success', 'message' => 'All Manifesto Comments Details', 'result' => $list], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/addReplyToManifestoComment",
     *     summary="Add new Reply to Manifesto Comment",
     *     tags={"Manifesto Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"manifestoPromisecommentId", "content","partyId"},
     *             @OA\Property(property="manifestoPromisecommentId", type="string", example="manifestopromiseId"),
     *             @OA\Property(property="content", type="string", example="comments"),
     *            @OA\Property(property="partyId", type="string", example="partyId")
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found "
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     **/
    public function addReplyToManifestoComment(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $parentCommentId = $request->manifestoPromisecommentId;
            $content = $request->content;
            $partyId = $request->partyId;
            $userType = empty($partyId) ? Auth::user()->getRoleNames()[0] : "Party";
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $comments = ManifestoComments::find($parentCommentId);
            $addReply = [
                "manifestocommentId" => $parentCommentId,
                "replyBy" => $userId,
                "replyByType" => $userType,
                "content" => $content,
                "createdBy" => Auth::user()->id,
                "updatedBy" => Auth::user()->id,
            ];
            ManifestoCommentsReply::create($addReply);
            $comments->repliesCount++;
            $comments->save();
            return response()->json(['status' => 'success', 'message' => 'Replied added'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/getManifestoCommentsReplies/{manifestocommentId}",
     *     summary="Fetch all Comments Replies",
     *     tags={"Manifesto Management"},
     *     @OA\Parameter(
     *         name="manifestocommentId",
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
    public function getManifestoCommentsReplies(string $id)
    {
        try {
            $partyId = request('partyId');
            $userId = $partyId ?? Auth::user()->id;
            $parentComment = ManifestoComments::find($id);
            $parentCommentDetails = [];
            $commentRepliesDetails = [];
            $totalReplies = 0;
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $page = request('page', 1);
            if ($parentComment) {
                $userType = $parentComment->authorType;
                $commentById = $parentComment->commentById;
                $isEditable = ($commentById == $userId);
                $createdAtDifference = $parentComment->createdAt->diffForHumans();
                $userDetails = null;
                $name = '';

                switch ($userType) {
                    case 'Leader':
                    case 'Citizen':
                        $userDetails = User::with(['userDetails', 'userAddress'])->find($commentById);
                        if ($userDetails) {
                            $name = $userDetails->firstName . ' ' . $userDetails->lastName;
                            $profileImage = $userDetails->userDetails ? $userDetails->userDetails->profileImage : null;
                            if ($userDetails->cityTown == '' && $userDetails->district == '') {
                                $address = $userDetails->userAddress->cityTown . " " . $userDetails->userAddress->district;
                            } else {
                                $address = $userDetails->cityTown . " " . $userDetails->district;
                            }
                        }
                        break;

                    case 'Party':
                        $userDetails = Party::find($commentById);
                        if ($userDetails) {
                            $name = $userDetails->name;
                            $address = '';
                            $profileImage = $userDetails->logo;
                        }
                        break;
                }

                if ($userDetails) {
                    $parentCommentDetails = [
                        'content' => $parentComment->content,
                        'name' => $name,
                        'address' => $address,
                        'profileImage' => $profileImage,
                        'commentId' => $parentComment->id,
                        'commentsByType' =>  $parentComment->authorType,
                        'isEditable' => $isEditable,
                        'createdAt' => $createdAtDifference,
                        'isParentComment' => true,
                        'repliesCount' => $parentComment->repliesCount,
                    ];
                }

                $perPage = env('PAGINATION_PER_PAGE', 10);
                $page = request('page', 1);
                $repliesQuery = ManifestoCommentsReply::where('manifestocommentId', $parentComment->id)->orderBy('createdAt', 'desc');
                $totalReplies = $repliesQuery->count();
                $replies = $repliesQuery->skip(($page - 1) * $perPage)->take($perPage)->get();

                foreach ($replies as $commentReply) {
                    $userType = $commentReply->replyByType;
                    $commentReplyBy = $commentReply->replyBy;
                    $isEditable = ($commentReplyBy == $userId);
                    $createdAtDifference = $commentReply->createdAt->diffForHumans();

                    $userDetails = null;
                    $name = '';

                    switch ($userType) {
                        case 'Leader':
                        case 'Citizen':
                            $userDetails = User::with(['userDetails', 'userAddress'])->find($commentReplyBy);
                            if ($userDetails) {
                                $name = $userDetails->firstName . ' ' . $userDetails->lastName;
                                $profileImage = $userDetails->userDetails ? $userDetails->userDetails->profileImage : null;
                                if ($userDetails->cityTown == '' && $userDetails->district == '') {
                                    $address = $userDetails->userAddress->cityTown . " " . $userDetails->userAddress->district;
                                } else {
                                    $address = $userDetails->cityTown . " " . $userDetails->district;
                                }
                            }
                            break;

                        case 'Party':
                            $userDetails = Party::find($commentReplyBy);
                            if ($userDetails) {
                                $name = $userDetails->name;
                                $address = '';
                                $profileImage = $userDetails->logo;
                            }
                            break;
                    }

                    if ($userDetails) {
                        $commentRepliesDetails[] = [
                            'content' => $commentReply->content,
                            'name' => $name,
                            'address' => $address,
                            'profileImage' => $profileImage,
                            'commentRepliesId' => $commentReply->id,
                            'replyByType' => $commentReply->replyByType,
                            'isEditable' => $isEditable,
                            'createdAt' => $createdAtDifference,
                            'isParentComment' => false,
                            'repliesCount' => null,
                            'currentPage' => $page
                        ];
                    }
                }
            }

            $list = new LengthAwarePaginator($commentRepliesDetails, $totalReplies, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'All Comments Details',
                'parentComment' => $parentCommentDetails,
                'commentReplies' => $list,
            ], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Server Error'], 500);
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    /**
     * @OA\Get(
     *     path="/api/viewPromises/{id}",
     *     summary="Fetch viewPromises by id",
     *     tags={"Manifesto Management"},
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
    public function viewPromises($id)
    {
        try {
            $item = ManifestoPromises::where('id', $id)
                ->with('manifesto.party', 'manifesto.electionHistory')
                ->first();
            if ($item == '') {
                return response()->json(['status' => 'error', 'message' => 'Manifesto Promises not found'], 400);
            }
          
      
          
            $currentPage = request('page', 1);
            $partyId = request('partyId');
            $electionName = ElectionType::where('id', $item->manifesto->electionHistory->electionTypeId)->first();
            $likes = new ManifestoLike();
            $likeByType = ($partyId !== null) ? 'Party' : Auth::user()->getRoleNames()[0];
            $likeById = ($partyId !== null) ? $partyId : Auth::user()->id;
            $likeType = $likes->hasLiked($likeByType, $likeById, $item->id);
            $isLiked = $likeType ? true : false;
            $userAddress = UserAddress::where('userId', Auth::user()->id)->first();
            $canReact = true;
            if($electionName=='LokSabha')
           {
            $canReact = true;
           }
            if ($userAddress != '' ) {
                $state = State::where('name', $userAddress->state)->first();
                if ($electionName != '' && $electionName =='Assembly') {
                    if ($state->id == $electionName->stateId) {
                        $canReact = true;
                    }
                }
            }
            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($item->id);
            $manifestoURL = $url . '/sharedManifesto/' . $encryptedPostId;

            $promises =
            [
                'manifestoSharedURL' => $manifestoURL,
                'manifestoPromiseId' => $item->id,
                'partyName' => $item->manifesto->party->name,
                'partyLogo' => $item->manifesto->party->logo,
                'electioNameYear' => 'Manifesto' . ' ' . $electionName->electionTypeDescriptionBrief,
                'createdAt' => $item->createdAt->diffForHumans(),
                'promiseDepartment' => $item->manifestoPromisesDepartment,
                'promiseTitle' => $item->manifestoPromisesPromise,
                'manifestoDescriptions' => $item->manifestoPromisesDescriptions,
                'likesCount' => $item->likesCount,
                'isLiked' => $isLiked,
                'commentsCount' => $item->commentsCount,
                'likedType' => $likeType,
                'currentPage' => $currentPage,
                'canReact' => $canReact
            ];

            return response()->json(['status' => 'success', 'message' => 'Promises Details', 'result' => $promises], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }

    public function sharedManifesto($id)
    {

        $promiseId = EncryptionHelper::decryptString($id);
        $item = ManifestoPromises::where('id',  $promiseId)
        ->with('manifesto.party', 'manifesto.electionHistory')
        ->first();
        return view('openSharedManifesto', [
            'promiseId' => $promiseId,
            'partyName' =>  $item ->manifesto->party->name,
            'manifestoPromises' => $item->manifestoPromisesPromise,
            'manifestoPromisesDepartment' => $item->manifestoPromisesDepartment,
            'partyImage' => $item->manifesto->party->logo,
             ]);

    }
}