<?php

namespace App\Http\Controllers\LikesController;

use App\Helpers\Action;
use App\Http\Controllers\Controller;
use App\Models\deviceKey;
use App\Models\FactCheck;
use App\Models\LeaderDetails;
use App\Models\Likes;
use App\Models\LogActivity;
use App\Models\Party;
use App\Models\PartyDetails;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikesManagementController extends Controller
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
     *     path="/api/like",
     *     summary="Add new Likes to post with different like types for removing likes reaction liketype is delete",
     *     tags={"Likes Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"authorType", "postId", "likedType" ,"partyId","createdBy"},
     *             @OA\Property(property="authorType", type="string", example="Leader/Citizen/Party/Super Admin"),
     *             @OA\Property(property="postId", type="string", example="postId"),
     *             @OA\Property(property="likedType", type="string", example="sad/happy/unlike/care/like/appreciate"),
     *            @OA\Property(property="createdBy", type="string", example="createdBy"),
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

    public function store(Request $request)
    {
        try {
            $authorType = $request->authorType;
            $postId = $request->postId;
            $partyId = $request->partyId;
            // if($authorType == "Party"){
            //     $postById = $request->partyId;
            //     if($partyId == ''){
            //         $fetch_partyId = PostByParty::find($postId);
            //         $postById = $fetch_partyId->partyId;
            //     }
            // }else {
            //     $postById = $request->createdBy;
            // }
            // return request()->all();
            // if($authorType == "Party"){
            //     $postById = $request->partyId;
            // }else {
            $postById = $request->createdBy;
            // }
            $likedType = $request->likedType;
            // return $postById;
            $userType = empty($partyId) ? Auth::user()->getRoleNames()[0] : "Party";
            $userId = empty($partyId) ? Auth::user()->id : $partyId;

            $postByModelClass = $this->getPostModelTypeClass($authorType);
            if($postByModelClass == null) {
                throw new Exception("Invalid User type.");
            }

            $likesExist = Likes::where('postid', $postId)->where('LikeById', $userId)->first();
            $existingLikeType = $likesExist->likeType ?? null;

            if ($likesExist && $likedType == $existingLikeType) {
                // Delete and Decrease - if like exists and reactions are same delete the records in likes table and decrease the counts in it's depandancy tables.
                if ($authorType == 'Leader' || $authorType == 'Party') {
                    $this->updateLeaderOrPartyDetailsCompetitiveCounts($authorType, $likedType, $postById, false);
                    
                }
                // decrease record counts in posted tables
                $this->postTablesUpdateByAuthor($postByModelClass, $postId, false);
                // Delete from Likes Table
                Likes::where([
                    'postid' => $postId,
                    'LikeById' => $userId,
                ])->delete();
            } else if($likesExist && $likedType != $existingLikeType) {
                // Update both decrease and increase - if like exists and reactions are different update the records in likes table and don't update any post type tables and update only competitive analyssis dependant tables
                if ($authorType == 'Leader' || $authorType == 'Party') {
                    $this->updateLeaderOrPartyDetailsCompetitiveCounts($authorType, $existingLikeType, $postById, false);
                    $this->updateLeaderOrPartyDetailsCompetitiveCounts($authorType, $likedType, $postById, true);
                }
                // update from likes table
                $this->insertOrUpdateLikesTable($postId, $userId, $authorType, $likedType, $userType);
            } else {
                // Add and Increase - if like doesn't exists add record in likes, all types of post and it's compeititive depandancy tables
                if ($authorType == 'Leader' || $authorType == 'Party') {
                    $this->updateLeaderOrPartyDetailsCompetitiveCounts($authorType, $likedType, $postById, true);
                }
                $this->postTablesUpdateByAuthor($postByModelClass, $postId, true);
                // insert in likes table
                $this->insertOrUpdateLikesTable($postId, $userId, $authorType, $likedType, $userType);
            }
            // Once done send Notification to respective Author
            // return $authorType." || ".$postById." || ".$partyId." || ".$postId;
        
            $this->sendLikeUpdatedNotification($authorType, $postById, $partyId,$postId);

            return response()->json(['status' => 'success', 'message' => 'Likes Updated',], 200);            
        } catch (\Exception $e) {
            return $e->getMessage().$e->getLine();
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    // Sub Functions
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

    function updateLeaderOrPartyDetailsCompetitiveCounts($authorType, $likedType, $authorId, $increment = true) {
        switch ($authorType) {
            case 'Leader':
                $authorDetails = LeaderDetails::where('leadersId', $authorId)->first();
                break;
            case 'Party':
                $authorDetails = PartyDetails::where('partyId', $authorId)->first();
                break;
            default:
                return null; 
        }
        if($authorDetails) {
            switch ($likedType) {
                case 'like':
                    if ($increment) {
                        $authorDetails->likePostCount++;
                    } else {
                        if ($authorDetails->likePostCount > 0) {
                            $authorDetails->likePostCount--;
                        } else {
                            $authorDetails->likePostCount = 0;
                        }
                    }
                    break;
        
                case 'care':
                    if ($increment) {
                        $authorDetails->carePostCount++;
                    } else {
                        if ($authorDetails->carePostCount > 0) {
                            $authorDetails->carePostCount--;
                        } else {
                            $authorDetails->carePostCount = 0;
                        }
                    }
                    break;
        
                case 'sad':
                    if ($increment) {
                        $authorDetails->sadPostCount++;
                    } else {
                        if ($authorDetails->sadPostCount > 0) {
                            $authorDetails->sadPostCount--;
                        } else {
                            $authorDetails->sadPostCount = 0;
                        }
                    }
                    break;
        
                case 'appreciate':
                    if ($increment) {
                        $authorDetails->appreciatePostCount++;
                    } else {
                        if ($authorDetails->appreciatePostCount > 0) {
                            $authorDetails->appreciatePostCount--;
                        } else {
                            $authorDetails->appreciatePostCount = 0;
                        }
                    }
                    break;
        
                case 'unlike':
                    if ($increment) {
                        $authorDetails->unlikesPostCount++;
                    } else {
                        if ($authorDetails->unlikesPostCount > 0) {
                            $authorDetails->unlikesPostCount--;
                        } else {
                            $authorDetails->unlikesPostCount = 0;
                        }
                    }
                    break;
        
                default:
                    break;
            }
            $authorDetails->save();
        }
        return $authorDetails;
    }
    
    function postTablesUpdateByAuthor($postByModelClass, $postId, $increment = true) {
        $postByModel = new $postByModelClass;
        // These updates values of postByCitizen, postByLeaders, postByParties, factCheck.
        $postDetails = $postByModel::find($postId);
        if ($increment) {
            $postDetails->likesCount++;
        } else {
            if ($postDetails->likesCount > 0) {
                $postDetails->likesCount--;
            } else {
                $postDetails->likesCount = 0;
            }
        }
        $postDetails->save();
    }

    function sendLikeUpdatedNotification($authorType, $postById, $partyId,$postId) {
        if ($authorType === 'Leader' || $authorType === 'Citizen') {
            $notificationType = 'reaction';
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
            $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
            Action::createNotification($userId, $authorType, $postById,$message,"Post",$postId,$authorType);
        } else if($authorType === 'Party') {
            // return "out";
            $notificationType = 'reaction';
            $getNotification = Action::getNotification('party', $notificationType);

            $users = ($partyId === '' || $partyId === null)
                ? User::with('userDetails')->find(Auth::user()->id)
                : Party::find($partyId);

            $party = Party::find($postById);
            if ($partyId == '') {
                $replaceArray = ['{name}' => $users->getFullName(), '{partyName}' => $party->name];
            } else {
                $replaceArray = ['{name}' => $users->name, '{partyName}' => $party->name];
            }
            $userId = ($partyId === '') ? ($users->userDetails ? $users->userDetails->userId : null) : $party->id;
            $userId = $partyId ?? Auth::user()->id;
            if (Auth::user()->id != $postById) {
                $userId = $partyId ?? Auth::user()->id;
                $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification($userId, $authorType, $postById,$message,"Post",$postId,$authorType);
            }
        }
    }

    function insertOrUpdateLikesTable($postId, $userId, $likedPostType, $likedType, $userType) {
        $likesTbl = Likes::updateOrInsert(
        [
            'postid' => $postId,
            'LikeById' => $userId,
        ],
        [
            'id' => DB::raw('gen_random_uuid()'),
            'postByType' => $likedPostType,
            'likeType' => $likedType,
            'LikeByType' => $userType,
            'createdBy' => Auth::user()->id,
            'updatedBy' => Auth::user()->id,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);

        return $likesTbl;
    }


        /**
     * @OA\Get(
     *     path="/api/like/{postId}",
     *     summary="Fetch likes details by postId",
     *     tags={"Likes Management"},
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
            $likes = Likes::where('postid', $id)->orderBy('createdAt', 'desc')->get();
            $likersDetails = [];
            foreach ($likes as $like) {
                $userType = $like->LikeByType;
                $userId = $like->LikeById;

                $userDetails = null;
                $profileImage = null;
                $name = '';

                switch ($userType) {
                    case 'Leader':
                    case 'Citizen':
                        $userDetails = User::with('userDetails')->find($userId);
                        if ($userDetails) {
                            $profileImage = $userDetails->userDetails ? $userDetails->userDetails->profileImage : null;
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
                        'likeType' => $like->likeType,
                        'profileImage' => $profileImage,
                        'likeByType' => $like->LikeByType,
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
 
}