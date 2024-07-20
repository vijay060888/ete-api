<?php

namespace App\Http\Controllers\CampaignController;

use App\Helpers\EncryptionHelper;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\AdsView;
use App\Models\PollsByLeaderDetails;
use App\Models\PollsByPartyDetails;
use App\Models\PostByLeaderMeta;
use App\Models\PostByParty;
use App\Models\PostByPartyMeta;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use App\Models\Ad;
use Auth;
use App\Helpers\EstimationReach;
use App\Helpers\BroadcastAds;
use App\Models\CampaignCredit;
use App\Models\User;
use App\Models\State;
use App\Models\AdTarget;
use App\Models\AdCampaignSetting;
use App\Models\PostByLeader;
use App\Models\AdPost;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;

class AdController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/ads",
     *     summary="Fetch all ads",
     *     tags={"Campaign Ad Management"},
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

    public function index()
    {
        try {
            $currentPage = request('page', 1);
            $keyword = request('keyword');
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $partyId = request('partyId');
            $keyword = request('keyword');
            $userId = !empty($partyId) ? $partyId : Auth::user()->id;

            // $ads = Ad::where('createdBy', $userId)
            //     ->where('adTitle', 'ILIKE', "$keyword%")
            //     ->orWhere('adTitle', 'ILIKE', "%$keyword%")
            //     ->where('status', '!=', 'Archived')
            //     ->orderBy('createdAt', 'desc')
            //     ->get();
            $ads = Ad::where('createdBy', $userId)
                        ->where('status', '!=', 'Archived')
                        ->orderByRaw("CASE 
                                        WHEN \"ads\".\"adTitle\" ILIKE '$keyword%' THEN 1
                                        WHEN \"ads\".\"adTitle\" ILIKE '%$keyword%' THEN 2
                                        ELSE 3
                                    END, \"createdAt\" DESC")
                        ->get();

            $uniqueCampaignNames = $ads->pluck('campaignName')->unique();
            $adArray = $uniqueCampaignNames->flatMap(function ($campaignName) use ($keyword, $partyId) {
                $currentPage = request('page', 1);
                $userId = !empty($partyId) ? $partyId : Auth::user()->id;
                $getAdDetails = $this->getAdDetails($keyword, $userId, $campaignName, $currentPage);
                if (count($getAdDetails) > 0) {
                    return [
                        [
                            'campaignName' => $campaignName,
                            'adDetails' => $getAdDetails,
                        ],
                    ];
                }

                return [];
            });

            $desiredTotal = $adArray->count();
            $pagedPosts = $adArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Campaign Ads', 'result' => $list], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    public function getAdDetails($keyword, $userId, $campaignName, $currentPage)
    {
        $ads = Ad::where('createdBy', $userId)
        ->where(function($query) use ($keyword) {
            $query->where('adTitle', 'ILIKE', "$keyword%")
                  ->orWhere('adTitle', 'ILIKE', "%$keyword%");
        })
        ->where(function($query) use ($campaignName) {
            $query->where('campaignName', 'ILIKE', "$campaignName%")
                  ->orWhere('campaignName', 'ILIKE', "%$campaignName%");
        })
            ->where('status', '!=', 'Archived')
            ->get();

        $ads->each(function ($ad) {
            if ($ad->endDate !== null && Carbon::parse($ad->endDate)->endOfDay()->isPast()) {
                $ad->status = 'Expired';
                $ad->save();
            }
        });

        $detailsArray = $ads->map(function ($ad) use ($currentPage) {
            return [
                'id' => $ad->id,
                'adTitle' => $ad->adTitle,
                'adMessage' => $ad->adMessage,
                'image' => $ad->image,
                'startDate' => $ad->startDate,
                'endDate' => $ad->endDate,
                'status' => $ad->status,
                'currentPage' => $currentPage
            ];
        });
        $filteredArray = $detailsArray->filter(function ($ads) {
            return is_array($ads) && isset($ads['startDate']) && isset($ads['endDate']);
        })->values();
        return $filteredArray;
    }

    public function getArchiveAdDetails($campaignName, $userId, $keyword, $currentPage)
    {
        $adsQuery = Ad::where('createdBy', $userId)
            ->where('status', 'Archived');

        if ($keyword != '') {
            $adsQuery->where(function($query) use ($keyword) {
                $query->where('adTitle', 'ILIKE', "$keyword%")
                      ->orWhere('adTitle', 'ILIKE', "%$keyword%");
            });
        }

        $ads = $adsQuery->get();

        $detailsArray = $ads->map(function ($ad) use ($currentPage) {
            return [
                'id' => $ad->id,
                'adTitle' => $ad->adTitle,
                'adMessage' => $ad->adMessage,
                'image' => $ad->image,
                'startDate' => $ad->startDate,
                'endDate' => $ad->endDate,
                'status' => $ad->status,
                'currentPage' => $currentPage
            ];
        });

        $filteredArray = $detailsArray->filter(function ($ads) {
            return is_array($ads) && isset($ads['startDate']) && isset($ads['endDate']);
        })->values();

        return $filteredArray;
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * @OA\Post(
     *     path="/api/ads",
     *     summary="Create Ad",
     *     tags={"Campaign Ad Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Ad Campaign",
     *     @OA\JsonContent(
     *         required={"partyId","campaignName","adTitle","adMessage","url", "hashtags", "image"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="campaignName", type="string", example="Test Campaign Name"),
     *         @OA\Property(property="adTitle", type="string", example="Test Ad Title"),
     *         @OA\Property(property="adMessage", type="string", example="Test Ad Message"),
     *         @OA\Property(property="url", type="string", example="https://testurl"),
     *         @OA\Property(property="hashtags", type="string", example="#test"),
     *         @OA\Property(property="startDate", type="startDate", example="startDate"),
     *         @OA\Property(property="endDate", type="endDate", example="endDate"),
     *         @OA\Property(property="startTime", type="startTime", example="startTime"),
     *         @OA\Property(property="endTime", type="endTime", example="endTime"),
     *         @OA\Property(property="image", type="string", example="testimage"),
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
     *       description="Unauthorized"
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
        try {
            $partyId = $request->partyId;
            $createdBy = $partyId ? $partyId : Auth::user()->id;
            $createdByType = $partyId ? "Party" : "Leader";
            $ads = Ad::create([
                'campaignName' => $request->campaignName,
                'adTitle' => $request->adTitle,
                'adMessage' => $request->adMessage,
                'url' => $request->url,
                'hashtags' => $request->hashtags,
                'image' => $request->image,
                'status' => 'Inactive',
                'createdBy' => $createdBy,
                'createdByType' => $createdByType,
            ]);
            return response()->json(['status' => 'success', 'message' => 'Ad campaign created succesfully', 'id' => $ads->id], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
    /**
    * @OA\Post(
    *     path="/api/getreach",
    *     summary="Add ",
    *     tags={"Campaign Ad Management"},
    *     @OA\RequestBody(
    *      required=true,
    *      description="Enter details",
    *     @OA\JsonContent(
    *         required={"partyId", "stateId", "constituency"},
    *         @OA\Property(property="partyId", type="string", example=""),
    *          @OA\Property(property="stateId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
    *          @OA\Property(property="stateDate", type="string", example="2024-02-24"),
    *          @OA\Property(property="endDate", type="string", example="2024-02-24"),
    *          @OA\Property(property="startTime", type="string", example="03:59 PM"),
    *          @OA\Property(property="endTime", type="string", example="03:59 PM"),
    *          @OA\Property(property="constituency", type="string", example=""),

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
    public function setEstimatedReach(Request $request)
    {

        try {
            $partyId = $request->partyId;
            $createdBy = $partyId ? $partyId : Auth::user()->id;
            $stateId = $request->stateId;
            $constituencyId = $request->constituency;
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $startTime = $request->startTime;
            $endTime = $request->endTime;
            $data = EstimationReach::getAdReachInfo($stateId, $constituencyId, $createdBy, $startDate, $endDate, $startTime, $endTime);
            return response()->json(['status' => 'success', 'message' => 'Ad campaign info', 'data' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }





    /**
     * @OA\Post(
     *     path="/api/publishAds",
     *     summary="Publish Broadcast",
     *     tags={"Campaign Ad Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Ad target",
     *      @OA\JsonContent(
     *         required={"partyId", "adId", "stateId","constituency", "image", "budget", "startDate", "endDate", "startTime", "endTime"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="adId", type="string", example="9b3cb02b-2930-47cd-b095-ac52da046fcf"),
     *         @OA\Property(property="stateId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
     *         @OA\Property(property="constituency", type="string", example=""),
     *          @OA\Property(property="startDate", type="string", example="2023-12-12"),
     *           @OA\Property(property="endDate", type="string", example="2023-12-13"),
     *          @OA\Property(property="startTime", type="string", example="12:00 am"),
     *          @OA\Property(property="endTime", type="string", example="12:01 am"),
     *          @OA\Property(property="budget", type="string", example="1234"),
     *         @OA\Property(property="image", type="string", example="testimage"),
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
     *       description="Unauthorized"
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function publish(Request $request)
    {
        //check if the credit is low, we show a buy more message....
        try {
            $partyId = $request->partyId;
            $createdBy = $partyId ? $partyId : Auth::user()->id;
            $stateId = $request->stateId;
            $constituencyId = $request->constituency ? $request->constituency : null;
            $adId = $request->adId;
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $startTime = $request->startTime;
            $endTime = $request->endTime;

            $data = EstimationReach::getAdReachInfo($stateId, $constituencyId, $createdBy, $startDate, $endDate, $startTime, $endTime);
            $confirmedReach = $data['confirmedReach'];
            $availableCredit = $data['availableCredit'];
            $requiredCredit = $data['requiredCredit'];
            if ($availableCredit < $requiredCredit) {
                $CreditToBeDeducted = $availableCredit;
            } else {
                $CreditToBeDeducted = $requiredCredit;
            }

            //message details ....
            $adDetails = Ad::find($adId);
            // return $request;
            $adDetails->status = 'Inactive';
            $adDetails->startDate = $startDate;
            $adDetails->endDate = $endDate;
            $adDetails->startTime = $startTime;
            $adDetails->endTime = $endTime;
            $adDetails->save();
            $message = $adDetails->adMessage;
            $title = $adDetails->adTitle;
            $hashtags = $adDetails->hashtags;


            //store the ad as post...
            if ($partyId) {
                $authorType = "Party";
                $postByParty = PostByParty::create([
                    'authorType' => $authorType,
                    "partyId" => $createdBy,
                    "postType" => "MultiMedia",
                    'hashTags' => $hashtags,
                    "postTitle" => $title,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'isAds' => true,
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]);
                $postId = $postByParty->id;
                $postByPartyMetaData = [
                    'postByPartyId' => $postId,
                    'postDescriptions' => $adDetails->adMessage,
                    "imageUrl1" => $adDetails->image,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,

                ];

                PostByPartyMeta::create($postByPartyMetaData);
            } else {
                $authorType = "Leader";
                $postByLeader = PostByLeader::create([
                    'authorType' => $authorType,
                    "leaderId" => $createdBy,
                    "postType" => "MultiMedia",
                    'hashTags' => $hashtags,
                    "postTitle" => $title,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'isAds' => true,
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]);
                $postId = $postByLeader->id;

                $postByLeaderMetaData = [
                    'postByLeaderId' => $postId,
                    'postDescriptions' => $adDetails->adMessage,
                    "imageUrl1" => $adDetails->image,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,

                ];

                PostByLeaderMeta::create($postByLeaderMetaData);
            }
            AdPost::create([
                'postId' => $postId,
                'authorType' => $authorType,
                'adsId' => $adId,
                'tobeReachedBy' => $confirmedReach,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);



            // deduct the credit....

            // insert the data into the database .....
            $constituencyType = "Assembly";
            AdTarget::create([
                'adId' => $adId,
                'stateId' => $request->stateId,
                'constituency' => $constituencyId,
                'constituencyType' => $constituencyType,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            AdCampaignSetting::create([
                'adId' => $adId,
                'budget' => $request->budget,
                'startDate' => $request->startDate,
                'endDate' => $request->endDate,
                'startTime' => $request->startTime,
                'endTime' => $request->endTime,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Ad publish successfull!', 'id' => $adDetails->id], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/adsView",
     *     summary="adsView",
     *     tags={"Campaign Ad Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="adsView",
     *      @OA\JsonContent(
     *         required={"postId", "partyId"},
     *         @OA\Property(property="postId", type="string", example=""),
     *         @OA\Property(property="partyId", type="string", example=""),
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
     *       description="Unauthorized"
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function adsView(Request $request)
    {
        $postId = $request->postId;
        $partyId = $request->partyId;
        $userId = !empty($partyId) ? $partyId : Auth::user()->id;

        $adsView = AdsView::firstOrNew(['postId' => $postId]);

        if (!$adsView->exists) {
            $adsView->postId = $postId;
            $adsView->viewBy = $userId;
            $adsView->viewCount = 1;
            $adsView->save();
        } elseif ($adsView->viewBy !== $userId) {
            $adsView->viewCount++;
            $adsView->viewBy = $userId;
            $adsView->save();
        }

        return response()->json(['status' => 'success', 'message' => 'Count Updated'], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/getAdsPerformance/{id}",
     *     summary="Fetch perfromance for your ads",
     *     tags={"Campaign Ad Management"},
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
    public function getAdsPerformance(string $id)
    {
        $ads = AdPost::where('adsId', $id)->pluck('postId')->toArray();
        $adsView = AdsView::whereIn('postId', $ads)->first();
        if ($adsView != '') {
            return response()->json(['status' => 'success', 'result' => "Your ads reach " . $adsView->viewCount . " people till now"], 200);

        } else {
            return response()->json(['status' => 'success', 'result' => "Your ads reach 0 people till now"], 200);
        }

    }


    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/api/ads/{id}",
     *     summary="Fetch ads by id",
     *     tags={"Campaign Ad Management"},
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
        try {
            $ads = Ad::find($id);
            $data = [
                'id' => $ads->id,
                'campaignName' => $ads->campaignName,
                'adTitle' => $ads->adTitle,
                'adMessage' => $ads->adMessage,
                'image' => $ads->image,
                'status' => $ads->status,
                'url' => $ads->url,
                'hashtags' => $ads->hashtags,
            ];
            return response()->json(['status' => 'success', 'message' => 'Campaign Ads', 'result' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
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
     * @OA\Put(
     *     path="/api/ads/{adId}",
     *     summary="Update Ad",
     *     tags={"Campaign Ad Management"},
     *     @OA\Parameter(
     *         name="adId",
     *         in="path",
     *         description="ID of the ad to be updated",
     *         required=true,
     *         @OA\Schema(type="integer", format="int64"),
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Update Ad Campaign",
     *         @OA\JsonContent(
     *             required={"partyId","campaignName","adTitle","adMessage","url", "hashtags", "image"},
     *             @OA\Property(property="partyId", type="string", example=""),
     *             @OA\Property(property="campaignName", type="string", example="Test Campaign Name"),
     *             @OA\Property(property="adTitle", type="string", example="Test Ad Title"),
     *             @OA\Property(property="adMessage", type="string", example="Test Ad Message"),
     *             @OA\Property(property="url", type="string", example="https://testurl"),
     *             @OA\Property(property="hashtags", type="string", example="#test"),
     *             @OA\Property(property="startDate", type="startDate", example="startDate"),
     *             @OA\Property(property="endDate", type="endDate", example="endDate"),
     *             @OA\Property(property="startTime", type="startTime", example="startTime"),
     *             @OA\Property(property="endTime", type="endTime", example="endTime"),
     *             @OA\Property(property="image", type="string", example="testimage"),
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
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found "
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     **/

    public function update(Request $request, string $id)
    {
        $ads = Ad::find($id);
        $status = $request->status;

        if ($status == $ads->status) {
            return response()->json(['message' => "Your ads is already been" . " " . $status], 400);
        }
        if ($status != '') {
            $ads->update(['status' => $status]);
            return response()->json(['status' => 'success', 'message' => "Status Updated"], 200);
        }

        $ads->update([
            'campaignName' => $request->campaignName ?: $ads->campaignName,
            'adTitle' => $request->adTitle ?: $ads->adTitle,
            'adMessage' => $request->adMessage ?: $ads->adMessage,
            'url' => $request->url ?: $ads->url,
            'hashtags' => $request->hashtags ?: $ads->hashtags,
            'image' => $request->image ?: $ads->image,
            'status' => $request->status ?: $ads->status,

        ]);
        return response()->json(['status' => 'success', 'message' => "Ads  Updated"], 200);
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
     *     path="/api/getArchieveAds",
     *     summary="Fetch all Archieve ads",
     *     tags={"Campaign Ad Management"},
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
    public function getArchieveAds()
    {
        try {
            $currentPage = request('page', 1);
            $keyword = request('keyword');
            $perPage = env('PAGINATION_PER_PAGE', 10);

            $partyId = request('partyId');
            $keyword = request('keyword');

            $userId = !empty($partyId) ? $partyId : Auth::user()->id;
            $ads = Ad::where('createdBy', $userId)
                        ->where(function($query) use ($keyword) {
                            $query->where('adTitle', 'LIKE', "$keyword%")
                                ->orWhere('adTitle', 'LIKE', "%$keyword%");
                        })
                        ->where('status', 'Archived')
                        ->get();
            $uniqueCampaignNames = $ads->pluck('campaignName')->unique();
            $adArray = $uniqueCampaignNames->map(function ($campaignName) use ($keyword, $partyId) {
                $userId = !empty($partyId) ? $partyId : auth()->id();
                $currentPage = request('page', 1);

                return [
                    'campaignName' => $campaignName,
                    'adDetails' => $this->getArchiveAdDetails($campaignName, $userId, $keyword, $currentPage),
                ];
            })->values();



            $desiredTotal = $adArray->count();
            $pagedPosts = $adArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return response()->json(['status' => 'success', 'message' => 'Campaign Ads', 'result' => $list], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/getAdsPreview/{id}",
     *     summary="Fetch getAdsPreview by id",
     *     tags={"Campaign Ad Management"},
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
    public function getAdsPreview($id)
    {
        try {
            $adPost = AdPost::where('adsId', $id)->first();
            $adsId = $adPost->postId;
            $post = PostByLeader::where('id', $adsId)->where('isAds', true)
                ->with([
                    'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                    'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
                    'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
                    'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
                    'user',
                    'user.userDetails',
                ])
                ->first();

            if ($post == '') {
                $post = PostByParty::where('id', $adsId)->where('isAds', true)->with([
                    'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                    'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
                    'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
                    'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
                ])
                    ->first();
            }

            // if($post == '')
            $url = env('APP_URL');
            $mainAds = Ad::find($id);
            $adUrl = $mainAds->url;
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Leaders";
            $authorType = $post->authorType;

            switch ($authorType) {
                case 'Leader':
                    $metas = $post->postByLeaderMetas;
                    $pollDetails = PollsByLeaderDetails::class;
                    $pollsById = "postByLeaderId";
                    $pollsVote = $post->pollsByLeaderVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByLeader;
                    $profileImage = $post->user->userDetails->profileImage;
                    break;
                case 'Party':
                    $metas = $post->postByPartyMetas;
                    $pollDetails = PollsByPartyDetails::class;
                    $pollsVote = $post->pollsByPartyVote;
                    $pollsById = "postByPartyId";
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByParty;
                    $profileImage = $post->party->logo;
                    break;
            }
            $creatorId = ($authorType == 'Citizen') ? 'citizenId' : (($authorType == 'Party') ? 'partyId' : 'leaderId');
            $createdBy = ($authorType == 'Citizen') ? $post->citizenId : (($authorType == 'Party') ? $post->partyId : $post->leaderId);
            $postByFullName = ($authorType == 'Citizen' || $authorType == 'Leader')
                ? (!empty($post->user) ? ($post->user->firstName . ' ' . $post->user->lastName) : 'Unknown Citizen')
                : (!empty($post->party) ? $post->party->name : 'Unknown Party');
            $authorType = $post->authorType;

            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=" . $authorType;
            if ($metas) {
                $imageUrls = [
                    optional($metas)->first()->imageUrl1 ?? null,
                    optional($metas)->first()->imageUrl2 ?? null,
                    optional($metas)->first()->imageUrl3 ?? null,
                    optional($metas)->first()->imageUrl4 ?? null,
                ];
                $ideaDepartment = $metas->pluck('ideaDepartment')->first();
                $postDescriptions = $metas->pluck('postDescriptions')->first();
            }
            if ($pollDetails != '') {
                $poll = $pollDetails::where($pollsById, $post->id)->first();
                $userVote = null;
                $isUserVoted = false;
                $selectedOption = '';
            }



            // $optionCounts = $poll->pluck('optionCount')->sortByDesc('optionCount')->toArray();

            // $totalSum = array_sum($optionCounts);

            // if ($totalSum !== 0) {
            //     $percentages = [];

            //     $data = [];
            //     foreach ($optionCounts as $count) {
            //         $percentage = ceil(($count / $totalSum) * 100);
            //         $data[] = ['count' => $count, 'percentage' => $percentage];
            //     }

            //     usort($data, function ($a, $b) {
            //         return $b['percentage'] - $a['percentage'];
            //     });

            //     $optionCounts = array_column($data, 'count');
            //     $percentages = array_column($data, 'percentage');
            // } else {
            //     $percentages = array_fill(0, count($optionCounts), 0);
            // }
            $formattedDate = $post->createdAt->diffForHumans();
            $adsId = AdPost::where('postId', $post->id)->first();
            $ads = Ad::find($adsId->adsId);
            $sponserLink = $ads->url;

            $postPreviewDetails = [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => !empty($post->user) ? $post->user->userName : 'Anonymous',
                'postByProfilePicture' => $profileImage,
                'isLiked' => false,
                'likedType' => null,
                'authorType' => $authorType,
                "$creatorId" => $createdBy,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'ideaDepartment' => (!empty($post) && !empty($post->postByLeaderMetas)) ? $post->postByLeaderMetas->pluck('ideaDepartment')->first() : null,
                'postDescriptions' => (!empty($post) && !empty($post->postByLeaderMetas)) ? $post->postByLeaderMetas->pluck('postDescriptions')->first() : null,
                'image' => $imageUrls,
                'pollOption' => (!empty($post) && !empty($post->pollsByLeaderDetails)) ?
                    $post->pollsByLeaderDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray() : [],
                'optionCount' => (!empty($post->pollsByLeaderDetails)) ? 0 : null,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => (!empty($post) && !empty($post->postByLeaderMetas)) ? $post->postByLeaderMetas->pluck('PollendDate')->first() : null,
                'pollendTime' => (!empty($post) && !empty($post->postByLeaderMetas)) ? $post->postByLeaderMetas->pluck('pollendTime')->first() : null,
                'complaintLocation' => (!empty($post) && !empty($post->postByLeaderMetas)) ? $post->postByLeaderMetas->pluck('complaintLocation')->first() : null,
                'optionLength' => (!empty($post->pollsByLeaderDetails)) ? count($post->pollsByLeaderDetails) : null,
                'eventsLocation' => (!empty($post->eventsByLeader)) ? $post->eventsByLeader->pluck('eventsLocation')->first() : null,
                'eventStartDate' => (!empty($post->eventsByLeader)) ? $post->eventsByLeader->pluck('startDate')->first() : null,
                'eventsEndDate' => (!empty($post->eventsByLeader)) ? $post->eventsByLeader->pluck('endDate')->first() : null,
                'eventStartTime' => (!empty($post->eventsByLeader)) ? $post->eventsByLeader->pluck('startTime')->first() : null,
                'eventsEndTime' => (!empty($post->eventsByLeader)) ? $post->eventsByLeader->pluck('endTime')->first() : null,
                'IsFollowing' => false,
                'IsEditable' => false,
                'postCreatedAt' => $formattedDate,
                'createdAt' => $post->createdAt,
                'complaintStatus' => null,
                'isOwnPost' => false,
                'createdBy' => $createdBy,
                'isCreatedByAdmin' => false,
                'isAds' => true,
                'sponserLink' => $sponserLink,
                'canVote' => false

            ];


            $otherAdsDetails =
            [
                "startDate" => $mainAds->startDate,
                "endDate" => $mainAds->endDate,
                "startTime" => $mainAds->startTime,
                "endTime" => $mainAds->endTime,
            ];

            $AdsTarget = AdTarget::where('adId', $id)->first();
            $stateId = $AdsTarget->stateId;
            $constituency = $AdsTarget->constituency;

            $creditDetails = EstimationReach::getAdReachInfo($stateId, $constituency, $mainAds->createdBy, $mainAds->startDate, $mainAds->endDate, $mainAds->startTime, $mainAds->endTime);
            $costPerCredit = 1;
            $cost = $creditDetails['requiredCredit'] * $costPerCredit;
            return response()->json(['status' => 'success', 'message' => 'Ads Preview', 'adsViewAsPost' => $postPreviewDetails, 'otherAdsDetails' => $otherAdsDetails, 'creditDetails' => $creditDetails, 'costPerCredit' => $costPerCredit, 'totalCost' => $cost, 'adsId' => $id], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }



    /**
     * @OA\Get(
     *     path="/api/finalPublished/{id}",
     *     summary="Fetch finalPublished by id",
     *     tags={"Campaign Ad Management"},
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
    public function finalPublished($id)
    {
        $ads = Ad::find($id);
        $adsTarget = AdTarget::where('adId', $id)->first();
        if ($adsTarget != '') {
            $stateId = $adsTarget->stateId;
            $constituencyId = $adsTarget->constituency ? $adsTarget->constituency : null;
            $createdBy = $ads->createdBy;

            $data = EstimationReach::getAdReachInfo($stateId, $constituencyId, $createdBy, $ads->startDate, $ads->endDate, $ads->startTime, $ads->endTime);

            $confirmedReach = $data['confirmedReach'];
            $availableCredit = $data['availableCredit'];
            $requiredCredit = $data['requiredCredit'];
            if ($availableCredit < $requiredCredit) {
                $CreditToBeDeducted = $availableCredit;
            } else {
                $CreditToBeDeducted = $requiredCredit;
            }

        }
        CampaignCredit::where('assignedTo', $createdBy)->decrement('credits', $CreditToBeDeducted);
        $ads->status = 'Active';
        $ads->save();
        return response()->json(['status' => 'success', 'message' => 'Ads successfully publish'], 200);

    }
}