<?php

namespace App\Http\Controllers\FollowController;

use DB;
use Auth;
use App\Models\User;
use App\Models\Party;
use App\Models\Leader;
use App\Helpers\Action;
use App\Helpers\Search;
use App\Models\UserDetails;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Models\LeaderDetails;
use App\Models\PartyFollowers;
use App\traits\BroadcastCount;
use Illuminate\Support\Carbon;
use App\Models\LeaderFollowers;
use App\Models\UserFollowerTag;
use App\Models\AssemblyConsituency;
use App\Models\LokSabhaConsituency;
use App\Http\Controllers\Controller;
use App\Models\AssemblyFollowerDetails;
use App\Models\PartyConsituencyFollowers;
use App\Models\PartyDetails;
use Illuminate\Pagination\LengthAwarePaginator;

class FollowManagementController extends Controller
{
    use BroadcastCount;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    /**
     * @OA\Get(
     *     path="/api/partyFollowUnfollow/{partyId}",
     *     summary="Follow or unfollow Party",
     *     tags={"Follow Management"},
     *     @OA\Parameter(
     *         name="partyId",
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
    public function followParty(Request $request, $partyId)
    {
        try {
            $userId = Auth::user()->id;
            $notificationRole = Auth::user()->getRoleNames()[0];
            $party = Party::where('id', $partyId)->first();
            $isFollowing = PartyFollowers::where('followerId', $userId)
                ->where('partyId', $partyId)
                ->exists();
            if ($isFollowing) {
                PartyFollowers::where('followerId', $userId)
                    ->where('partyId', $partyId)
                    ->delete();

                $party->followercount--;
                $party->save();
                $partyDetails = PartyDetails::where('partyId', $partyId)->first();
                $gender = Auth::user()->gender;
                switch ($gender) {
                    case 'MALE':
                        if ($partyDetails->maleFollowers > 0) {
                            $partyDetails->maleFollowers--;
                        }
                        break;

                    case 'FEMALE':
                        if ($partyDetails->femaleFollowers > 0) {
                            $partyDetails->femaleFollowers--;
                        }
                        break;

                    case 'TRANSGENDER':
                        if ($partyDetails->transgenderFollowers > 0) {
                            $partyDetails->transgenderFollowers--;
                        }
                        break;

                    default:
                        // Handle other cases if needed
                        break;
                }
                $birthdate = Auth::user()->DOB;
                $birthdate = date_create_from_format('d-m-Y', $birthdate);
                $currentDate = new \DateTime();
                $age = $currentDate->diff($birthdate)->y;
                if ($age <= 35) {
                    $partyDetails->youngUsers--;
                }
                if ($age >= 36) {
                    $partyDetails->middledAgeUsers--;
                }
                $partyDetails->followersCount--;
                $partyDetails->save();
                $message = 'Unfollowed the party.';
            } else {
                PartyFollowers::create([
                    'followerId' => $userId,
                    'partyId' => $partyId,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id
                ]);
                $party->followercount++;
                $party->save();

                $data = [
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'id' => \DB::raw('gen_random_uuid()'),
                ];
                PartyDetails::updateOrInsert([
                    'partyId' => $partyId
                ], $data);
                $PartyDetails = PartyDetails::where('partyId', $partyId)->first();
                $gender = Auth::user()->gender;
                switch ($gender) {
                    case 'MALE':
                        $PartyDetails->maleFollowers++;
                        break;

                    case 'FEMALE':
                        $PartyDetails->femaleFollowers++;
                        break;

                    case 'TRANSGENDER':
                        $PartyDetails->transgenderFollowers++;
                        break;
                    default:
                        break;

                }
                $birthdate = Auth::user()->DOB;
                $birthdate = date_create_from_format('d-m-Y', $birthdate);
                $currentDate = new \DateTime();
                $age = $currentDate->diff($birthdate)->y;
                if ($age <= 35) {
                    $PartyDetails->youngUsers++;
                }
                if ($age >= 36) {
                    $PartyDetails->middledAgeUsers++;
                }
                $PartyDetails->followersCount++;
                $PartyDetails->save();

                $replaceArray = ['{name}' => Auth::user()->firstName, '{partyName}' => $party->name];
                $notificationType = 'follow';
                $UserType = 'Party';
                $getNotification = Action::getNotification('party', $notificationType);
                $messagenot = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification(Auth::user()->id, $UserType, $partyId, $messagenot,"Follow",$userId,$notificationRole);
                $message = 'Followed the party.';
            }

            return response()->json(['status' => 'success', 'message' => $message], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/leaderFollowUnfollow/{leaderId}",
     *     summary="Follow or unfollow Leader",
     *     tags={"Follow Management"},
     *     @OA\Parameter(
     *         name="leaderId",
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
    public function followLeader(Request $request, $leaderId)
    {
        try {
            $userId = Auth::user()->id;
            $notificationRole = Auth::user()->getRoleNames()[0];
            if ($userId == $leaderId) {
                return response()->json(['status' => 'error', 'message' => "You cannot follow yourself."], 400);
            }
            $leader = Leader::where('leadersId', $leaderId)->first();
            $isFollowing = LeaderFollowers::where('followerId', $userId)
                ->where('leaderId', $leaderId)
                ->exists();
            if ($isFollowing) {
                LeaderFollowers::where('followerId', $userId)
                    ->where('leaderId', $leaderId)
                    ->delete();
                $leader->followercount--;
                $leader->save();

                $leaderDetails = LeaderDetails::where('leadersId', $leaderId)->first();
                $gender = Auth::user()->gender;
                switch ($gender) {
                    case 'MALE':
                        if ($leaderDetails->maleFollowers > 0) {
                            $leaderDetails->maleFollowers--;
                        }
                        break;

                    case 'FEMALE':
                        if ($leaderDetails->femaleFollowers > 0) {
                            $leaderDetails->femaleFollowers--;
                        }
                        break;

                    case 'TRANSGENDER':
                        if ($leaderDetails->transgenderFollowers > 0) {
                            $leaderDetails->transgenderFollowers--;
                        }
                        break;

                    default:
                        // Handle other cases if needed
                        break;
                }



                $birthdate = Auth::user()->DOB;
                $birthdate = date_create_from_format('d-m-Y', $birthdate);
                $currentDate = new \DateTime();
                $age = $currentDate->diff($birthdate)->y;
                if ($age <= 35) {
                    $leaderDetails->youngUsers--;
                }
                if ($age >= 36) {
                    $leaderDetails->middledAgeUsers--;
                }
                $leaderDetails->followersCount--;
                $leaderDetails->save();

                $message = 'Unfollowed the leader.';
            } else {
                LeaderFollowers::create([
                    'followerId' => $userId,
                    'leaderId' => $leaderId,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id
                ]);
                $leader->followercount++;
                $leader->save();
                $data = [
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'id' => \DB::raw('gen_random_uuid()'),
                ];
                LeaderDetails::updateOrInsert([
                    'leadersId' => $leaderId
                ], $data);
                $leaderDetails = LeaderDetails::where('leadersId', $leaderId)->first();
                $gender = Auth::user()->gender;
                switch ($gender) {
                    case 'MALE':
                        $leaderDetails->maleFollowers++;
                        break;

                    case 'FEMALE':
                        $leaderDetails->femaleFollowers++;
                        break;

                    case 'TRANSGENDER':
                        $leaderDetails->transgenderFollowers++;
                        break;
                    default:
                        break;

                }
                $birthdate = Auth::user()->DOB;
                $birthdate = date_create_from_format('d-m-Y', $birthdate);
                $currentDate = new \DateTime();
                $age = $currentDate->diff($birthdate)->y;
                if ($age <= 35) {
                    $leaderDetails->youngUsers++;
                }
                if ($age >= 36) {
                    $leaderDetails->middledAgeUsers++;
                }
                $leaderDetails->followersCount++;
                $leaderDetails->save();

                $userId = Auth::user()->id;
                $replaceArray = ['{name}' => Auth::user()->getFullName()];
                $notificationType = 'follow';
                $UserType = 'Leader';
                $getNotification = Action::getNotification('userLeader', $notificationType);
                $messagenot = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                Action::createNotification($userId, $UserType, $leaderId,$messagenot,"Follow", $userId,$notificationRole );

                $message = 'Followed the leader.';
            }
            return response()->json(['status' => 'success', 'message' => $message], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/followUnfollowConsituency/{consituencyId}",
     *     summary="Follow or unfollow Consituency",
     *     tags={"Follow Management"},
     *     @OA\Parameter(
     *         name="consituencyId",
     *         in="path",
     *         required=true,
     *         description="The ID of the Consituency to follow or unfollow."
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
     *   security={{ "apiAuth": {} }}
     * )
     */

    public function followUnfollowConsituency(Request $request, $consituencyId)
    {
        try {
            $userId = Auth::user()->id;
            $user = User::find($userId);
            $userType = $user->getRoleNames()[0];
            $loksabha_id = null;
            $assembly_id = null;
            if ($consituencyId !== '') {
                $assembly = AssemblyConsituency::find($consituencyId);
                $loksabha = LokSabhaConsituency::find($consituencyId);

                if ($assembly) {
                    $consituencyname = $assembly->name;
                    $assembly_id = $assembly->id;
                }

                if ($loksabha) {
                    $consituencyname = $loksabha->name;
                    $loksabha_id = $loksabha->id;
                }
            }
            $consituencyFollower = UserFollowerTag::where('followedTags', $consituencyname)->where('userId', Auth::user()->id)->exists();
            $getUserBelongsTo = UserDetails::where('userId', $userId)->where('assemblyId',$assembly_id)->first();
            $userbelongsto = $getUserBelongsTo ? true : false;
            
            if ($consituencyFollower) {
                UserFollowerTag::where('userId', $userId)
                    ->where('followedTags', $consituencyname)
                    ->delete();
                    if($userbelongsto != true && $assembly_id){
                    $this->insertOrUpdateUserAssemblyFollowersForBroadCast($assembly_id, $user->gender,$user->DOB,false, false);
                    }
                $message = 'Unfollowed the consituency';
            } else {
                $createConsituencyFollowerTag = [
                    'userId' => Auth::user()->id,
                    'followedTags' => $consituencyname,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'userType' => $userType,
                    'assembly_id' => $assembly_id,
                    'loksabha_id' => $loksabha_id,
                ];
                UserFollowerTag::create($createConsituencyFollowerTag);
                if($userbelongsto != true && $assembly_id){
                $this->insertOrUpdateUserAssemblyFollowersForBroadCast($assembly_id, $user->gender,$user->DOB,true, true);
                }
                $message = 'Followed the consituency';
            }
            return response()->json(['status' => 'success', 'message' => $message], 200);

        } catch (\Exception $e) {
            dd($e->getMessage());
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }

    

    // function updateAgeRangeKey($ageRangeArr, $dob, $increment) {
    //     $birthdate = Carbon::parse($dob);
    //     $currentDate = Carbon::now();
    //     $userAge = $currentDate->diffInYears($birthdate);
    //     $AgeKey = floor($userAge / 10) * 10;
    //     if (isset($ageRangeArr[$AgeKey])) {
    //         $ageRangeArr[$AgeKey] = ($increment) ? ($ageRangeArr[$AgeKey] + 1) : (($ageRangeArr[$AgeKey] - 1 < 0) ? 0 : $ageRangeArr[$AgeKey] - 1);
    //     }        
    //     return $ageRangeArr;
    // }

    /**
     * @OA\Get(
     *     path="/api/partyYouarenotFollowing",
     *     summary="partyYouarenotFollowing",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function partyYouarenotFollowing(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $perPage = $request->query('per_page', 10);
            $partiesNotFollowing = Party::whereNotIn('id', function ($query) use ($userId) {
                $query->select('partyId')
                    ->from('party_followers')
                    ->where('followerId', $userId);
            })
                ->select('id', 'name', 'logo')
                ->paginate($perPage);

            $partiesNotFollowing->getCollection()->transform(function ($party) {
                $stateCode = !empty($party->getStateCode()) ? $party->getStateCode() : '';
                return [
                    'partyId' => $party->id,
                    'name' => $party->name,
                    'logo' => $party->logo,
                    'type' => $party->type,
                    'stateCode' => $stateCode,
                ];
            });
            return response()->json(['status' => 'success', 'parties' => $partiesNotFollowing->items(),'current_page' => $partiesNotFollowing->currentPage(),
            'last_page' => $partiesNotFollowing->lastPage(),], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/leadersYouAreNotFollowing",
     *     summary="leadersYouAreNotFollowing",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function leadersYouAreNotFollowing()
    {
        // $perPage = env('PAGINATION_PER_PAGE', 10);
        $perPage = 10;
        $loggedInUserId = Auth::user()->id;

        $leadersYouAreFollowing = LeaderFollowers::where('followerId', $loggedInUserId)
            ->pluck('leaderId');

        $leadersYouAreNotFollowing = DB::table('users')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->select(
                'users.id as leaderId',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->where('model_has_roles.model_type', '=', 'App\Models\User')
            ->whereNotIn('users.id', $leadersYouAreFollowing)
            ->where('roles.name', 'Leader')
            ->where('users.id', '!=', $loggedInUserId) // Exclude the logged-in user
            ->paginate($perPage);
            $paginatedleaders = $leadersYouAreNotFollowing->map(function ($leader) {
                return $leader;
            });
            $currentPage = $leadersYouAreNotFollowing->currentPage();
            $lastPage = $leadersYouAreNotFollowing->lastPage();
        return response()->json(['status' => 'success', 'leaders' => $paginatedleaders, 'current_page' => $currentPage,
        'last_page' => $lastPage], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/constituenciesYouAreNotFollowing",
     *     summary="constituenciesYouAreNotFollowing",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */

    public function constituenciesYouAreNotFollowing(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            // $perPage = env('PAGINATION_PER_PAGE', 10);
            $perPage = 10;
            $page = $request->query('page', 1);

            $consituency = UserFollowerTag::where("userId", $userId)->get();
            $followingConsituency = [];
            $assemblyConstituencyIds = [];
            $loksabhaConstituencyIds = [];
            foreach ($consituency as $consituencies) {
                $assemblyId = AssemblyConsituency::where('name', $consituencies->followedTags)->pluck('id')->toArray();
                if (!empty($assemblyId)) {
                    $assemblyConstituencyIds = array_merge($assemblyConstituencyIds, $assemblyId);
                }
                
                $lokSabha = LokSabhaConsituency::where('name', $consituencies->followedTags)->pluck('id')->toArray();
                if (!empty($lokSabha)) {
                    $loksabhaConstituencyIds = array_merge($loksabhaConstituencyIds, $lokSabha);
                }
            }
            $assembylyAlreadyFollow = array_unique($assemblyConstituencyIds);
            $loksabhaAlreadyFollow = array_unique($loksabhaConstituencyIds);
            $assembly = AssemblyConsituency::whereNotIn('id', $assembylyAlreadyFollow)->get();
            $lokSabha = LokSabhaConsituency::whereNotIn('id', $loksabhaAlreadyFollow)->get();
            $consituency = $lokSabha->sortBy('name')->concat($assembly);
            $transformedConsituency = $consituency->map(function ($item) {
                return [
                    'consituencyId' => $item->id,
                    'consituencyName' => $item->name,
                    'logo' => $item->logo,
                    'type' => $item->type
                ];
            });
            $totalItems = $transformedConsituency->count();
            $lastPage = ceil($totalItems / $perPage);
            $page = max(min($page, $lastPage), 1);

            $paginator = new LengthAwarePaginator(
                $transformedConsituency->forPage($page, $perPage),
                $totalItems,
                $perPage,
                $page,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
            $formattedData = $paginator->values();
            return response()->json(['status' => 'success', 'parties' => $formattedData, 'current_page' => $paginator->currentPage(),
            'last_page' => $lastPage], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }

    }



    /**
     * @OA\Get(
     *     path="/api/leaderYouFollow",
     *     summary="leaderYouFollow",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not foun "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function leaderYouFollow()
    {
        // $perPage = env('PAGINATION_PER_PAGE', 10);
        $perPage = 10;

        $leaderFollowers = LeaderFollowers::where('followerId', Auth::user()->id)
            ->join('users', 'leader_followers.leaderId', '=', 'users.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->select(
                'leader_followers.id as leaderFollowingId',
                'users.id as leaderId',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->orderBy('leader_followers.createdAt', 'desc')
            ->paginate($perPage);

            $currentPage = $leaderFollowers->currentPage();
            $lastPage = $leaderFollowers->lastPage();

            $mappedLeaders = $leaderFollowers->map(function ($follower) use ($currentPage){
                $follower['currentPage'] = $currentPage;
                return $follower;
            });
            
        return response()->json(['status' => 'success', 'leader' => $mappedLeaders, 'current_page' => $currentPage,
        'last_page' => $lastPage], 200);

    }
    /**
     * @OA\Get(
     *     path="/api/partyYouFollow",
     *     summary="partyYouFollow",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function partyYouFollow()
    {
        try {
            // $perPage = env('PAGINATION_PER_PAGE', 10);
            $perPage = 10;

            $partyFollowers = PartyFollowers::where('followerId', Auth::user()->id)
                ->with(['party:id,name,logo'])
                ->orderBy('party_followers.createdAt', 'desc')
                ->paginate($perPage);

                $currentPage = $partyFollowers->currentPage();
                $lastPage = $partyFollowers->lastPage();

                $mappedParties = $partyFollowers->map(function ($follower) use ($currentPage) {
                    $stateCode = !empty($follower->party->getStateCode()) ? $follower->party->getStateCode() : '';
                    return [
                        'followingId' => $follower->id,
                        'partyId' => $follower->party->id,
                        'name' => $follower->party->name,
                        'logo' => $follower->party->logo,
                        'stateCode' => $stateCode,
                        'currentPage' => $currentPage,
                    ];
                });
            return response()->json(['status' => 'success', 'parties' => $mappedParties, 'current_page' => $currentPage,
            'last_page' => $lastPage], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/consituencyYouFollow",
     *     summary="consituencyYouFollow",
     *     tags={"Follow Management"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
  public function consituencyYouFollow(Request $request)
{
    try {
        $userId = Auth::user()->id;
        // $perPage = env('PAGINATION_PER_PAGE', 10);
        $perPage = 10;
        $page = $request->query('page', 1);

        $consituency = UserFollowerTag::where("userId", $userId)->orderBy('user_follower_tags.createdAt', 'desc')->get();

        $consituencyMapped = $consituency->map(function ($item) use($page) {
            $consituency = AssemblyConsituency::where('name', $item->followedTags)->orderBy('assembly_consituencies.createdAt', 'desc')->first();

            if ($consituency === null) {
                $consituency = LokSabhaConsituency::where('name', $item->followedTags)->orderBy('lok_sabha_consituencies.createdAt', 'desc')->first();
            }

            $consituencyId = $consituency ? $consituency->id : null;
            return [
                "consituencyId" => $consituencyId,
                "consituencyName" => $item->followedTags,
                'logo' => null,
                'currentPage' => $page
            ];
        });

        $consituencyMapped = $consituencyMapped->reject(function ($item) {
            return $item['consituencyId'] === null;
        });

        $totalItems = $consituencyMapped->count();
        $lastPage = ceil($totalItems / $perPage);

        $page = max(min($page, $lastPage), 1);


        $paginator = new LengthAwarePaginator(
            $consituencyMapped->forPage($page, $perPage),
            $totalItems,
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $formattedData = $paginator->values();
        return response()->json(['status' => 'success', 'parties' => $formattedData, 'current_page' => $paginator->currentPage(),
        'last_page' => $lastPage], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
    }

}


    /**
     * @OA\POST(
     *     path="/api/searchfollowingLeader",
     *     summary="Search searchfollowingLeader",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyword"),
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
    public function searchfollowingLeader(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $searchLeadears = Search::searchLeaderYouFollow($keyword);
            return response()->json(['status' => 'success', 'result' => $searchLeadears], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }

    /**
     * @OA\POST(
     *     path="/api/searchfollowingParty",
     *     summary="Search searchfollowingParty",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyword"),
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
    public function searchfollowingParty(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $searchParty = Search::searchPartyYouFollow($keyword);
            return response()->json(['status' => 'success', 'result' => $searchParty], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
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
    /**
     * @OA\Get(
     *     path="/api/getYourFollowers",
     *     summary="Fetch all your followers",
     *     tags={"Follow Management"},
     *     @OA\Parameter(
     *         name="partyId",
     *         in="query",
     *         description="Party ID to filter roles (optional)",
     *         required=false,
     *         @OA\Schema(type="string")
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

    public function getYourFollowers(Request $request)
    {
        $partyId = $request->partyId;
        $userId = Auth::user()->id;

        $followerModel = ($partyId == '') ? LeaderFollowers::class : PartyFollowers::class;
        $modelId = ($partyId == '') ? 'leaderId' : 'partyId';
        $modelvalue = ($partyId == '') ? $userId : $partyId;

        // $perPage = env('PAGINATION_PER_PAGE', 10);
        $perPage = 100;


        $userFollowers = $followerModel::where("$modelId", $modelvalue)
            ->with(['follower.userDetails'])
            ->paginate($perPage);

        $userFollowers->getCollection()->transform(function ($follower) {
            $userDetails = User::find($follower->follower->id);
            $roleName = $userDetails->getRoleNames()[0];
            return [
                'userId' => $follower->follower->id,
                'name' => $follower->follower->firstName . " " . $follower->follower->lastName,
                'userType' => $roleName,
                'profileImage' => !empty($follower->follower->userDetails) ? $follower->follower->userDetails->profileImage : null,
            ];
        });

        return response()->json(['status' => 'success', 'result' => $userFollowers], 200);

    }


    /**
     * @OA\Get(
     *     path="/api/partyFollowUnfollowConsituency/{consituencyId}",
     *     summary="Follow or unfollow consituency from partyApp",
     *     tags={"Follow Management"},
     *     @OA\Parameter(
     *         name="consituencyId",
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
    public function partyFollowUnfollowConsituency($id)
    {
        $consituencyId = $id;
        $partyId = request('partyId');
        $party = Party::find($partyId);
        if ($party == '') {
            return response()->json(['status' => 'success', 'message' => 'Party does not exist'], 200);
        }
        $consituency = AssemblyConsituency::find($consituencyId);
        if ($consituency) {
            $consituencyType = "Assembly";
        } else {
            $consituency = LokSabhaConsituency::find($consituencyId);
            $consituencyType = "Lok Sabha";
        }

        if ($consituency) {
            $tag = $consituency->name;
        } else {
            return response()->json(['status' => 'success', 'message' => 'Invalid consituencyId or consituency no longer exists'], 400);
        }


        $followingData = [
            'followerId' => $party->id,
            'consituencyId' => $consituencyId,
            'consituencyType' => $consituencyType,
            'tag' => $tag,
            'createdBy' => Auth::user()->id,
            'updatedBy' => Auth::user()->id
        ];

        $checkFollowing = PartyConsituencyFollowers::where('followerId', $party->id)->where('consituencyId', $consituencyId)->exists();
        if ($checkFollowing) {
            PartyConsituencyFollowers::where('followerId', $party->id)->where('consituencyId', $consituencyId)->delete();
            return response()->json(['status' => 'success', 'message' => 'Unfollowed consituency'], 200);
        } else {
            PartyConsituencyFollowers::create($followingData);
            return response()->json(['status' => 'success', 'message' => 'Followed consituency'], 200);
        }

    }

    /**
     * @OA\Get(
     *     path="/api/getConsituencyPartyFollow",
     *     summary="Fetch all  getConsituencyPartyFollow",
     *     tags={"Follow Management"},
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
    public function getConsituencyPartyFollow()
    {
        $partyId = request('partyId');
        $party = Party::find($partyId);
        if ($party == '') {
            return response()->json(['status' => 'error', 'message' => 'Party does not exist'], 400);
        }

        $partyConsituencyFollowers = PartyConsituencyFollowers::where('followerId', $partyId)->orderBy('createdAt', 'desc')->get();
        $modifiedFollowers = $partyConsituencyFollowers->map(function ($follower) {

            if ($follower->type == 'Assembly') {
                $consituency = AssemblyConsituency::find($follower->consituencyId);
            } else {
                $consituency = LokSabhaConsituency::find($follower->consituencyId);
            }

            if (!$consituency) {
                if ($follower->type == 'Assembly') {
                    $consituency = LokSabhaConsituency::find($follower->consituencyId);
                } else {
                    $consituency = AssemblyConsituency::find($follower->consituencyId);
                }
            }
            if ($consituency) {
                return [
                    'consituencyId' => $consituency->id,
                    'consituencyName' => $consituency->name,
                    'logo' => $consituency->logo
                ];
            }

        })->filter()->values();


        $currentPage = request()->input('page', 1);

        $perPage = 100;

        $paginator = new LengthAwarePaginator(
            $modifiedFollowers->forPage($currentPage, $perPage),
            $modifiedFollowers->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current(), 'query' => request()->query()]
        );

        return response()->json(['status' => 'success', 'result' => $paginator], 200);
    }


    /**
     * @OA\Get(
     *     path="/api/getConsituencyPartyNotFollowed",
     *     summary="Fetch all  getConsituencyPartyFollow",
     *     tags={"Follow Management"},
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
    public function getConsituencyPartyNotFollowed()
    {
        $partyId = request('partyId');
        $party = Party::find($partyId);

        if (!$party) {
            return response()->json(['status' => 'error', 'message' => 'Party does not exist'], 400);
        }

        $allConsituencies = AssemblyConsituency::all()->concat(LokSabhaConsituency::all());

        $partyConsituencyFollowers = PartyConsituencyFollowers::where('followerId', $partyId)->get();
        $followedConsituencyIds = $partyConsituencyFollowers->pluck('consituencyId')->toArray();

        $notFollowedConsituencies = $allConsituencies->reject(function ($consituency) use ($followedConsituencyIds) {
            return in_array($consituency->id, $followedConsituencyIds);
        });

        $notFollowedConsituenciesData = $notFollowedConsituencies->map(function ($consituency) {
            return [
                'consituencyId' => $consituency->id,
                'consituencyName' => $consituency->name,
                'logo' => $consituency->logo,
            ];
        });
        $notFollowedConsituenciesData = $notFollowedConsituenciesData->values();
        $perPage = 100;

        $paginator = new LengthAwarePaginator(
            $notFollowedConsituenciesData->forPage(1, $perPage),
            $notFollowedConsituenciesData->count(),
            $perPage,
            1,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return response()->json(['status' => 'success', 'result' => $paginator], 200);
    }


}