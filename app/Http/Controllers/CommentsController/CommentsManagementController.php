<?php

namespace App\Http\Controllers\CommentsController;

use App\Helpers\Action;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentReply;
use App\Models\Party;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use App\Models\User;
use App\Helpers\HttpHelper;
use App\Models\FactCheck;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class CommentsManagementController extends Controller
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
     *     path="/api/comment",
     *     summary="Add new comment to post",
     *     tags={"Comments Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"authorType", "postId", "content","partyId"},
     *             @OA\Property(property="authorType", type="string", example="Leader/Citizen"),
     *             @OA\Property(property="postId", type="string", example="postId"),
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
    public function store(Request $request)
    {
        try {
            $commentPostByType = $request->authorType;
            $postId = $request->postId;
            $content = $request->content;
            $verifyText = HttpHelper::checkText($content);
            $verifyText = $verifyText->result;
            if($verifyText == 'abusive'){
                return response()->json(['status' => 'error', 'message' => "System detect content was in-appropriate and can not be allowed."], 404);
            }  
            $partyId = $request->partyId;
            $userType = empty($partyId) ? Auth::user()->getRoleNames()[0] : "Party";
            $commentById = empty($partyId) ? Auth::user()->id : $partyId;
            $postByModelClass = $this->getPostModelTypeClass($commentPostByType);
            if ($postByModelClass) {
                $postByModel = new $postByModelClass;
                $postDetails = $postByModel::find($postId);
                $postDetails->commentsCount++;
                $postDetails->save();
            }

            if ($commentPostByType == 'Leader' || $commentPostByType == 'Citizen') {
                $notificationType = 'comment';
                $postByLeader = $postByModelClass::where('id', $postId)->first();
                $getNotification = Action::getNotification('userLeader', $notificationType);
                $party = ($partyId === '' || $partyId === null) ? User::with('userDetails')->find(Auth::user()->id) : Party::find($partyId);
                if ($partyId == '') {
                    $replaceArray = ['{name}' => $party->getFullName()];
                } else {
                    $replaceArray = ['{name}' => $party->name];
                }
                $userId = ($party instanceof User) ? ($party->userDetails ? $party->userDetails->userId : null) : $party->id;
                $postById = ($commentPostByType == 'Leader') ? $postByLeader->leaderId : $postByLeader->citizenId;
                $userId = $partyId ?? Auth::user()->id;
                if (Auth::user()->id != $postById)
                 {
                    $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                    Action::createNotification($userId, $commentPostByType, $postById, $message,"Post",$postId,$commentPostByType);
                 }
            } else if($commentPostByType != 'Super Admin') {
                $notificationType = 'comment';
                $postByParty = $postByModelClass::where('id', $postId)->first();
                $getNotification = Action::getNotification('party', $notificationType);
                $user = ($partyId === '' || $partyId === null) ? User::with('userDetails')->find(Auth::user()->id) : Party::find($partyId);
                $party = Party::find($postByParty->partyId);

                if ($partyId == '') {
                    $replaceArray = ['{name}' => $user->getFullName(), '{partyName}' => $party->name];
                } else {
                    $replaceArray = ['{name}' => $user->name, '{partyName}' => $party->name];
                }
                $userId = $partyId??Auth::user()->id;
                $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification($userId, $commentPostByType, $party->id,$message,"Post",$postId,$commentPostByType );
            }

            Comment::create([
                'postid' => $postId,
                'commentById' => $commentById,
                'postByType' => $commentPostByType,
                'content' => $content,
                'commentByType' => $userType,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Your Comments is Posted'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    function getPostModelTypeClass($likedPostType) {
        switch ($likedPostType) {
            case 'Leader':
                $postByModelClass = PostByLeader::class;
                break;
            case 'Citizen':
                $postByModelClass = PostByCitizen::class;
                break;
            case 'Party':
                $postByModelClass = PostByParty::class;
                break;
            case 'Super Admin':
                $postByModelClass = FactCheck::class;
                break;
            default:
                $postByModelClass = null;
        }

        return $postByModelClass;
    }
    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/comment/{postId}",
     *     summary="Fetch all Comments",
     *     tags={"Comments Management"},
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
    public function show(string $id)
    {
        try {
            $partyId = request('partyId');
            $userId = $partyId ?? Auth::user()->id;
            $comments = Comment::where('postid', $id)->orderBy('createdAt', 'desc')->get();
            $commentsDetails = [];
            
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $page = request('page', 1);

            foreach ($comments as $comment) {
                $userType = $comment->commentByType;
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
                            $address = "";
                            $profileImage = $userDetails->logo;
                        }
                        break;
                }

                if ($userDetails) {
                    $commentsDetails[] = [
                        'content' => $comment->content,
                        'name' => $name,
                        'userType' =>  $comment->commentByType,
                        'userId' => $userDetails->id,
                        'address' => $address,
                        'profileImage' => $profileImage,
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
            return response()->json(['status' => 'success', 'message' => 'All Comments Details', 'result' => $list], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
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
    /**
     *   @OA\Put(
     *   path="/api/comment/{commentId}",
     *     summary="Update comments by commentId",
     *     tags={"Comments Management"},
     *     @OA\Parameter(
     *         name="commentId",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="content", type="string", example="Comment content here"),
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
            $commentId = $id;
            $content = $request->content;
            $verifyText = HttpHelper::checkText($content);
            $verifyText = $verifyText->result;
            if($verifyText == 'abusive'){
                return response()->json(['status' => 'error', 'message' => "System detect content was in-appropriate and can not be allowed."], 404);
            }
            $comment = Comment::find($commentId);
            $comment->content = $content;
            $comment->save();
            return response()->json(['status' => 'success', 'message' => 'Comments Updated Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/addReplyToComment",
     *     summary="Add reply to the comment",
     *     tags={"Comments Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"commentId", "content" ,"partyId"},
     *             @OA\Property(property="commentId", type="string", example="commentId"),
     *             @OA\Property(property="content", type="string", example="comments"),
     *            @OA\Property(property="partyId", type="string", example="partyId"),
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
    public function addReplyToComment(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $parentCommentId = $request->commentId;
            $content = $request->content;
            $verifyText = HttpHelper::checkText($content);
            $verifyText = $verifyText->result;
            if($verifyText == 'abusive'){
                return response()->json(['status' => 'error', 'message' => "System detect content was in-appropriate and can not be allowed."], 404);
            }
            $partyId = $request->partyId;
            $userType = empty($partyId) ? Auth::user()->getRoleNames()[0] : "Party";
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $comments = Comment::find($parentCommentId);
            $addReply = [
                "commentId" => $parentCommentId,
                "replyBy" => $userId,
                "replyByType" => $userType,
                "content" => $content,
                "createdBy" => Auth::user()->id,
                "updatedBy" => Auth::user()->id,

            ];
            CommentReply::create($addReply);
            $comments->repliesCount++;
            $comments->save();
            if ($comments->commentByType == 'Leader' || $comments->commentByType == 'Citizen') {
                $notificationType = 'commentreply';

                $getNotification = Action::getNotification('userLeader', $notificationType);
                $party = ($partyId === '' || $partyId === null)
                    ? User::with('userDetails')->find(Auth::user()->id)
                    : Party::find($partyId);
                if ($partyId == '') {
                    $replaceArray = ['{name}' => $party->getFullName()];
                } else {
                    $replaceArray = ['{name}' => $party->name];
                }
                $userId = ($party instanceof User) ? ($party->userDetails ? $party->userDetails->userId : null) : $party->id;
                $postById = $comments->commentById;
                $userId = $partyId ?? Auth::user()->id;
                $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification($userId, $comments->commentByType, $postById, $message,"Post",$comments->postid,$comments->postByType);

            } else {
                $notificationType = 'commentreply';
                $getNotification = Action::getNotification('party', $notificationType);
                $user = ($partyId === '' || $partyId === null)
                    ? User::with('userDetails')->find(Auth::user()->id)
                    : Party::find($partyId);

                $party = Party::find($comments->commentById);

                if ($partyId == '') {
                    $replaceArray = ['{name}' => $user->getFullName(), '{partyName}' => $party->name];

                } else {
                    $replaceArray = ['{name}' => $user->name, '{partyName}' => $party->name];
                }
                $userId = $partyId??Auth::user()->id;
                $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification($userId, $comments->commentByType, $party->id,$message,"Post",$comments->postid,$comments->postByType);

            }
            return response()->json(['status' => 'success', 'message' => 'Replies Added'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/getCommentsReplies/{commentId}",
     *     summary="Fetch all Comments Replies",
     *     tags={"Comments Management"},
     *     @OA\Parameter(
     *         name="commentId",
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
    public function getCommentsReplies(string $id)
    {
        try {
            $userId = Auth::user()->id;
            $parentComment = Comment::find($id);
            $parentCommentDetails = [];
            $commentRepliesDetails = [];
            $totalReplies = 0;
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $page = request('page', 1);
            if ($parentComment) {
                $userType = $parentComment->commentByType;
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
                        'isEditable' => $isEditable,
                        'userType' => $parentComment->commentByType,
                        'createdAt' => $createdAtDifference,
                        'isParentComment' => true,
                        'repliesCount' => $parentComment->repliesCount,
                    ];
                }

                $perPage = env('PAGINATION_PER_PAGE', 10);
                $page = request('page', 1);
                $repliesQuery = CommentReply::where('commentId', $parentComment->id)->orderBy('createdAt', 'desc');
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
                            'userType' => $commentReply->replyByType,
                            'isEditable' => $isEditable,
                            'createdAt' => $createdAtDifference,
                            'isParentComment' => false,
                            'repliesCount' => null,
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
     *   @OA\Put(
     *   path="/api/updateRepliesComment/{commentRepliesId}",
     *     summary="Update comments Replies by repliesID",
     *     tags={"Comments Management"},
     *     @OA\Parameter(
     *         name="commentRepliesId",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="content", type="string", example="Comment content here"),
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
    public function updateRepliesComment(Request $request, string $id)
    {
        try {
            $commentId = $id;
            $content = $request->content;
            $verifyText = HttpHelper::checkText($content);
            $verifyText = $verifyText->result;
            if($verifyText == 'abusive'){
                return response()->json(['status' => 'error', 'message' => "System detect content was in-appropriate and can not be allowed."], 404);
            }
            $comment = CommentReply::find($commentId);
            $comment->content = $content;
            $comment->save();
            return response()->json(['status' => 'success', 'message' => 'Comments Updated Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Delete(
     *     path="/api/comment/{commentId}",
     *     summary="Delete Comments  by id",
     *     tags={"Comments Management"},
     *     @OA\Parameter(
     *         name="commentId",
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
            $commentId = $id;
            $comment = Comment::find($commentId);
            $commentPostByType = $comment->postByType;
            $postId = $comment->postid;
            $postByModelClass = $this->getPostModelTypeClass($commentPostByType);
      
            if ($postByModelClass) {
                $postByModel = new $postByModelClass;
                $postDetails = $postByModel::find($postId);
                $postDetails->commentsCount--;
                $postDetails->save();
            }
            $comment->delete();
            return response()->json(['status' => 'success', 'message' => 'Comments Delete Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/deleteCommentReplies/{commentrepliesId}",
     *     summary="Delete Comments  by commentrepliesId",
     *     tags={"Comments Management"},
     *     @OA\Parameter(
     *         name="commentrepliesId",
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
    public function deleteCommentReplies(string $id)
    {
        try {
            $commentId = $id;
            $commentReplies = CommentReply::find($commentId);
            $parentComment = Comment::find($commentReplies->commentId);
            $parentComment->repliesCount--;
            $parentComment->save();
            $commentReplies->delete();
            return response()->json(['status' => 'success', 'message' => 'Comments Replies Delete Successfully'], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
}