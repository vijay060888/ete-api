<?php

namespace App\Http\Controllers\CampaignController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\deviceKey;
use App\Models\Notification;
use App\Models\SMSTemplate;
use Illuminate\Http\Request;
use App\Models\Broadcast;
use App\Helpers\EstimationReach;
use App\Helpers\BroadcastAds;
use App\Helpers\Action;
use App\Models\CampaignCredit;
use App\Models\User;
use App\Models\State;
use App\Models\BroadcastTarget;
use App\Models\CampaignSetting;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BroadcastController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/broadcasts",
     *     summary="Fetch all broadcasts",
     *     tags={"Campaign Broadcast Management"},
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
            $userId = $partyId ?? Auth::user()->id;
            $broadcasts =   Broadcast::when($keyword, function ($query) use ($keyword) {
                                return $query->where('campaignName', 'ILIKE', "%$keyword%");
                            })
                            ->where('createdBy', $userId)
                            ->where('status', '!=', 'Archived')
                            ->orderBy('createdAt','desc')
                            ->get();

            $uniqueCampaignNames = $broadcasts->pluck('campaignName')->unique();
            $broadcastArray = $uniqueCampaignNames->map(function ($item) use ($userId, $keyword, $currentPage) {
            $broadcastDetails = $this->getBroadcastDetails($item, $userId, $keyword, $currentPage);
                if (!empty($broadcastDetails)) {
                    return [
                        'campaignName' => $item,
                        'broadcastDetails' => $broadcastDetails,
                    ];
                }
            })->filter()->values();

            $desiredTotal = $broadcastArray->count();
            $pagedPosts = $broadcastArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);


            return response()->json(['status' => 'success', 'message' => 'Campaign Broadcasts', 'result' => $list], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    public function getBroadcastDetails($campaignName, $userId, $keyword, $currentPage)
    {
        $broadcasts = Broadcast::when($keyword, function ($query) use ($keyword) {
            return $query->where('broadcastTitle', 'ILIKE', "%$keyword%");
        })
        ->where('createdBy', $userId)
        ->where('campaignName', $campaignName)
        ->where('status', '!=', 'Archived')
        ->get();
                $detailsArray = $broadcasts->map(function ($broadcast) use ($currentPage) {
            $campaign_details = CampaignSetting::where('broadcastId', $broadcast->id)->first();
                        $startDate = $campaign_details ? $campaign_details->startDate : null;
            $startTime = $campaign_details ? $campaign_details->startTime : null;
            $broadcastType = $campaign_details ? $campaign_details->broadcastType : null;

            return [
                'id' => $broadcast->id,
                'broadcastTitle' => $broadcast->broadcastTitle,
                'image' => $broadcast->image,
                'broadcastMessage' => $broadcast->broadcastMessage,
                'status' => $broadcast->status,
                'broadcastType' => $broadcastType,
                'startDate' => $startDate,
                'startTime' => $startTime,
                'currentPage' => $currentPage
            ];
        });
        $filteredArray = $detailsArray->filter(function ($broadcast) {
            return is_array($broadcast) && isset($broadcast['startDate']) && isset($broadcast['startTime']);
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
     *     path="/api/broadcasts",
     *     summary="Create Broadcast",
     *     tags={"Campaign Broadcast Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Broadcast Campaign",
     *     @OA\JsonContent(
     *         required={"partyId","campaignName","broadcastTitle","broadcastMessage","url", "hashtags", "image"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="campaignName", type="string", example="Test Campaign Name"),
     *         @OA\Property(property="broadcastTitle", type="string", example="Test Broadcast Title"),
     *         @OA\Property(property="broadcastMessage", type="string", example="Test Campaign Name"),
     *         @OA\Property(property="url", type="string", example="https://testurl"),
     *         @OA\Property(property="hashtags", type="string", example="#test"),
     *         @OA\Property(property="image", type="string", example="testimage"),
     *         @OA\Property(property="smsId", type="string", example="smsId"),
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
            $broadCast = Broadcast::create([
                'campaignName' => $request->campaignName,
                'broadcastTitle' => $request->broadcastTitle,
                'broadcastMessage' => $request->broadcastMessage,
                'url' => $request->url,
                'hashtags' => $request->hashtags,
                'image' => $request->image,
                'createdBy' => $createdBy,
                'createdByType' => $createdByType,
                'smsId' => $request->smsId,
                'status' => 'Pending'
            ]);
            return response()->json(['status' => 'success', 'message' => 'Broadcast campaign created succesfully', 'result' => $broadCast->id], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/broadcasts/reach",
     *     summary="Set Broadcast reach",
     *     tags={"Campaign Broadcast Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Broadcast target",
     *      @OA\JsonContent(
     *         required={"partyId", "stateId", "constituency", "gender", "minAge", "maxAge"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="stateId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
     *         @OA\Property(property="constituency", type="string", example=""),
     *         @OA\Property(property="gender", type="string", example=""),
     *         @OA\Property(property="minAge", type="string", example=""),
     *         @OA\Property(property="maxAge", type="string", example=""),
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
    public function setEstimatedReach(Request $request)
    {
        try {
        $partyId = $request->partyId;
        $createdBy = $partyId ? $partyId : Auth::user()->id;
        $stateId = $request->stateId;
        $constituencyId = $request->constituency;
        $minAge = $request->minAge;
        $maxAge = $request->maxAge;
        $gender = $request->gender;
        $data = EstimationReach::getReachInfo($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy);
        return response()->json(['status' => 'success', 'message' => 'Broadcast campaign info', 'data' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/broadcasts/publish",
     *     summary="Publish Broadcast",
     *     tags={"Campaign Broadcast Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Broadcast target",
     *      @OA\JsonContent(
     *         required={"partyId", "broadcastId", "stateId","constituency","gender","minAge", "maxAge", "image", "broadcastType", "startDate", "startTime"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="broadcastId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
     *         @OA\Property(property="stateId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
     *         @OA\Property(property="constituency", type="string", example=""),
     *         @OA\Property(property="gender", type="string", example=""),
     *         @OA\Property(property="minAge", type="string", example=""),
     *         @OA\Property(property="maxAge", type="string", example=""),
     *         @OA\Property(property="broadcastType", type="string", example="whatsapp"),
     *         @OA\Property(property="whTemplate", type="string", example="promo2"),
     *          @OA\Property(property="startDate", type="string", example="2023-12-12"),
     *          @OA\Property(property="startTime", type="string", example="12:00 am"),
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
        $senderType = ($partyId != '') ? Auth::user()->getRoleNames()->first() : 'Party';
        $createdBy = $partyId ? $partyId : Auth::user()->id;
        $stateId = $request->stateId;
        $constituencyId = $request->constituency ? $request->constituency : null;
        $minAge = $request->minAge;
        $maxAge = $request->maxAge;
        $gender = $request->gender;
        $broadcastId = $request->broadcastId;
        $data = EstimationReach::getReachInfo($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy);
        // return $broadcastId;
        $availableCredit = $data['availableCredit'];
        $requiredCredit = $data['requiredCredit'];
        $confirmReached = $data['confirmedReach'];
        if ($availableCredit < $requiredCredit) {
            $CreditToBeDeducted = $availableCredit;
        } else {
            $CreditToBeDeducted = $requiredCredit;
        }
        $credits = CampaignCredit::where('assignedTo', $createdBy)->first();
        // return $credits;
        if ($credits == '' || $credits->credits == 0) {
            return response()->json(['status' => 'error', 'message' => "You don't have enough credits"], 400);
        }
        CampaignSetting::create([
            'id' => DB::raw('gen_random_uuid()'),
            'broadcastId' => $broadcastId,
            'broadcastType' => $request->broadcastType,
            'whTemplate' => $request->input('whTemplate', ''),
            'startDate' => $request->startDate,
            'startTime' => $request->startTime,
            'createdAt' => now(),
            'updatedAt' => now()
        ]);

        //message details ....

        $broadcastDetails = Broadcast::find($broadcastId);
        $message = $broadcastDetails->broadcastMessage;
        $title = $broadcastDetails->broadcastTitle;
        $broadcastid = $broadcastDetails->id;
        $smsId = $broadcastDetails->smsId;
        $smsTemplate = '';
       
        $broadcastTargetType = $request->broadcastType;
        $pluckType = ($broadcastTargetType == 'whatsapp') ? "phone" : null;
        $userIds = EstimationReach::getUserDetails($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy, $confirmReached, $pluckType);
        if ($broadcastTargetType == "notification") {
            // send the notifications to users ....
            $deviceTokens = BroadcastAds::getUserDetails($userIds);
            BroadcastAds::notificationAdsOrBroadcast($deviceTokens, $message, $title);
            foreach(  $userIds as $userId)
            {
                $user = User::find($userId);
                $userType = $user->getRoleNames()[0];
                // $notificationMessage =  $title;
                $createNotificationData = [
                    'type' => $userType,
                    'typeId' =>  $user->id,
                    'isBroadcast' => true,
                    'isRead' =>0,
                    'userId' =>  $createdBy,
                    'notificationMessage' => $title,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'userType' => ($partyId != '') ? Auth::user()->getRoleNames()->first() : 'Party',
                    'broadcast_id' => $broadcastid,
                    'notificationtype' => "Broadcast",
                    'notificationtypeid' => $broadcastId,
                    'notificationcategory' => ($partyId != '') ? Auth::user()->getRoleNames()->first() : 'Party',
                ];
                Notification::create($createNotificationData);
            }
        } else if ($broadcastTargetType == "sms") {
            // send the sms
            if($smsId != '')
            {
               $smsTemplate = SMSTemplate::find($smsId);
            }
    
            if($smsTemplate=='') {
                return response()->json(['status' => 'error', 'message' => 'You have not selected any sms template'], 400);
            } else {
                $sms = $smsTemplate->SMSType;
                $sms = ucfirst(strtolower($sms));
            }

            $getTemplate  = Action::getSMSTemplate($sms);

            foreach ($userIds as $userId) {
                $user = User::find($userId);
                $userFirstName = $user->firstName;
                $userLastName = $user->lastName;
                $userPhoneNumber = $user->phoneNumber;
                $fullName = $userFirstName . " " . $userLastName;

                if ($getTemplate) {
                    foreach ($getTemplate['data'] as $d) {
                        $content = '';
                        $param = $d->parameter;

                        if ($param == "{#var#}") {
                            $content = str_replace("{#var#}", $fullName, $d->template);
                        } else {
                            $randomNumber = mt_rand(100000, 999999);
                            $content = str_replace("{#var#}", $randomNumber, $d->template);
                        }

                        $details = [
                            'template' => $content,
                            'tempid' => $d->tempid,
                            'entityid' => $d->entityid,
                            'source' => $d->source
                        ];
                        Action::sendSMS($details, $userPhoneNumber);
                    }
                } 
            }
       
        } else if ($broadcastTargetType == 'whatsapp') {
            $phoneNumbers = array_unique($userIds);
            $templateName = $request->whTemplate ?? 'promo2';
            $response = EstimationReach::sendWhatsappNotification($templateName, $phoneNumbers);
            if ($response['status'] != 'success') {
                return response()->json(['status'=> false, 'error'=> $response['data']], 500);   
            }
        }

        CampaignCredit::where('assignedTo', $createdBy)->decrement('credits', $CreditToBeDeducted);

        $constituencyType = "Assembly";
        BroadcastTarget::create([
            'id' => DB::raw('gen_random_uuid()'),
            'broadcastId' => $broadcastId,
            'stateId' => $request->stateId,
            'constituency' => $constituencyId,
            'constituencyType' => $constituencyType,
            'gender' => $request->gender,
            'minAge' => $request->minAge,
            'maxAge' => $request->maxAge,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);

        Broadcast::find($broadcastId)->update(['status' => 'Active']);

        return response()->json(['status' => 'success', 'message' => 'Broadcast created successfull!'], 200);

        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/broadcasts/publishlater",
     *     summary="Publish Broadcast",
     *     tags={"Campaign Broadcast Management"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Create Broadcast target",
     *      @OA\JsonContent(
     *         required={"partyId", "broadcastId", "stateId","constituency","gender","minAge", "maxAge", "image", "broadcastType", "startDate", "startTime"},
     *         @OA\Property(property="partyId", type="string", example=""),
     *         @OA\Property(property="broadcastId", type="string", example="de0323e5-169b-4a6e-a1f7-5a1f727e978d"),
     *         @OA\Property(property="stateId", type="string", example=""),
     *         @OA\Property(property="constituency", type="string", example=""),
     *         @OA\Property(property="gender", type="string", example=""),
     *         @OA\Property(property="minAge", type="string", example=""),
     *         @OA\Property(property="maxAge", type="string", example=""),
     *          @OA\Property(property="broadcastType", type="string", example="sms"),
     *          @OA\Property(property="whTemplate", type="string", example="whTemplateName"),
     *          @OA\Property(property="startDate", type="string", example="2023-12-12"),
     *          @OA\Property(property="startTime", type="string", example="12:00 am"),
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
    public function publishlater(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $senderType = $partyId ? Auth::user()->getRoleNames()->first() : 'Party';
            $createdBy = $partyId ? $partyId : Auth::user()->id;
            $stateId = $request->stateId;
            $constituencyId = $request->constituency ? $request->constituency : null;
            $minAge = $request->minAge;
            $maxAge = $request->maxAge;
            $gender = $request->gender;
            $broadcastId = $request->broadcastId;
            $estimatedReach = new EstimationReach();
            $data = $estimatedReach->getBroadcastReachCount($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy);
            $availableCredit = $data['availableCredit'];
            $requiredCredit = $data['requiredCredit'];
            if ($availableCredit < $requiredCredit) {
                $CreditToBeDeducted = $availableCredit;
            } else {
                $CreditToBeDeducted = $requiredCredit;
            }
            $credits = CampaignCredit::where('assignedTo', $createdBy)->first();
            $userCredits = $credits->credits ?? 0;
            if ($userCredits == 0 ||  $userCredits < $CreditToBeDeducted) {
                return response()->json(['status' => 'error', 'message' => "You don't have enough credits"], 400);
            }
            // "startDate": "2024-05-23",
            // "startTime": "11:10 am",

            $startDate = $request->startDate;
            $startTime = $request->startTime;
            $startDateTimeStr = "$startDate $startTime";
        
            $startDateTime = Carbon::createFromFormat('Y-m-d h:i a', $startDateTimeStr);
            $currentDateTime = Carbon::now();
            $diffInMinutes = $currentDateTime->diffInMinutes($startDateTime, false);

            if ($diffInMinutes < 1) {
                // return response()->json(['status' => 'error', 'message' => "Start date and time should be at least 5 minutes before now"], 400);
                return response()->json(['status' => 'error', 'message' => "Start date and time should be greater than now"], 400);
            }   

            DB::beginTransaction();
            CampaignSetting::create([
                'id' => DB::raw('gen_random_uuid()'),
                'broadcastId' => $broadcastId,
                'broadcastType' => $request->broadcastType,
                'whTemplate' => $request->input('whTemplate', null),
                'startDate' => $request->startDate,
                'startTime' => $request->startTime,
                'createdAt' => now(),
                'updatedAt' => now()
            ]);

            CampaignCredit::where('assignedTo', $createdBy)->decrement('credits', $CreditToBeDeducted);
            $constituencyType = "Assembly";
            BroadcastTarget::create([
                'id' => DB::raw('gen_random_uuid()'),
                'broadcastId' => $broadcastId,
                'stateId' => $request->stateId,
                'constituency' => $constituencyId,
                'constituencyType' => $constituencyType,
                'gender' => $request->gender,
                'minAge' => $request->minAge,
                'maxAge' => $request->maxAge,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Broadcast created successfully!'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server error"], 404);
        }
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/api/broadcasts/{id}",
     *     summary="Fetch broadcasts by id",
     *     tags={"Campaign Broadcast Management"},
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
            $currentPage = request('page', 1);
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $broadcast = Broadcast::find($id);
            $campaign_details = CampaignSetting::where('broadcastId', $broadcast->id)->first();
            $startDate = $campaign_details ? $campaign_details->startDate : null;
            $startTime = $campaign_details ? $campaign_details->startTime : null;
            $broadcastType = $campaign_details ? $campaign_details->broadcastType : null;

            $data = [
                'id' => $broadcast->id,
                'campaignName' => $broadcast->campaignName,
                'broadcastTitle' => $broadcast->broadcastTitle,
                'image' => $broadcast->image,
                'broadcastMessage' => $broadcast->broadcastMessage,
                'status' => $broadcast->status,
                'broadcastType' => $broadcastType,
                'url' => $broadcast->url,
                'hashtags' => $broadcast->hashtags,
                'smsId' =>  $broadcast->smsId, 
                'startDate' => $startDate,
                'startTime' => $startTime,
            ];

            return response()->json(['status' => 'success', 'message' => 'Campaign Broadcasts', 'result' => $data], 200);
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
     * @OA\PUT(
     *     path="/api/broadcasts/{id}",
     *     summary="Update Broadcast",
     *     tags={"Campaign Broadcast Management"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="UUID of the broadcast",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             format="uuid",
     *             example="e1f21b4c-2e6f-4d17-bdca-0a9b66f982bb",
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Update Broadcast Campaign",
     *         @OA\JsonContent(
     *             required={"partyId", "campaignName", "broadcastTitle", "broadcastMessage", "url", "hashtags", "image"},
     *             @OA\Property(property="partyId", type="string", example=""),
     *             @OA\Property(property="campaignName", type="string", example="Test Campaign Name"),
     *             @OA\Property(property="broadcastTitle", type="string", example="Test Broadcast Title"),
     *             @OA\Property(property="broadcastMessage", type="string", example="Test Campaign Name"),
     *             @OA\Property(property="url", type="string", example="https://testurl"),
     *             @OA\Property(property="hashtags", type="string", example="#test"),
     *             @OA\Property(property="image", type="string", example="testimage"),
     *             @OA\Property(property="status", type="string", example="status"),
     *             @OA\Property(property="smsId", type="string", example="smsId"),
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
     */

    public function update(Request $request, string $id)
    {
        $partyId = $request->partyId;
        $broadcast = Broadcast::find($id);
        $status = $request->status;
        if ($status != '') {
            $broadcast->status = $status;
            $broadcast->save();
            return response()->json(['message' => 'Status Updated']);
        }

        if ($broadcast) {
            $broadcast->update([
                'campaignName' => $request->campaignName ?? $broadcast->campaignName,
                'broadcastTitle' => $request->broadcastTitle ?? $broadcast->broadcastTitle,
                'broadcastMessage' => $request->broadcastMessage ?? $broadcast->broadcastMessage,
                'url' => $request->url ?? $broadcast->url,
                'hashtags' => $request->hashtags ?? $broadcast->hashtags,
                'image' => $request->image ?? $broadcast->image,
                'smsId' => $request->smsId ?? $broadcast->smsId,
            ]);
            return response()->json(['message' => 'Broadcast updated successfully']);
        } else {
            return response()->json(['error' => 'Broadcast not found'], 404);
        }

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
     *     path="/api/getArchiveBroadcast",
     *     summary="Fetch all Archieve broadcasts",
     *     tags={"Campaign  Broadcast Management"},
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
    public function getArchiveBroadcast()
    {
        try {
            $currentPage = request('page', 1);
            $keyword = request('keyword');
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $partyId = request('partyId');
            $userId = $partyId ?? Auth::user()->id;
            $broadcasts = Broadcast::when($keyword, function ($query) use ($keyword) {
                return $query->where('campaignName', 'ILIKE', "%$keyword%");
            })
            ->where('createdBy', $userId)
            ->where('status', 'Archived')
            ->get();

            $uniqueCampaignNames = $broadcasts->pluck('campaignName')->unique();
            $broadcastArray = $uniqueCampaignNames->map(function ($item) use ($userId, $keyword, $currentPage) {
                $broadcastDetails = $this->getArchiveBroadcastDetails($item, $userId, $keyword, $currentPage);
                if (count($broadcastDetails) != 0) {
                    return [
                        'campaignName' => $item,
                        'broadcastDetails' => $broadcastDetails,
                    ];
                }
            })->filter()->values();

            $desiredTotal = $broadcastArray->count();
            $pagedPosts = $broadcastArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);


            return response()->json(['status' => 'success', 'message' => 'Campaign Broadcasts', 'result' => $list], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    public function getArchiveBroadcastDetails($campaignName, $userId, $keyword, $currentPage)
    {
        $broadcasts = Broadcast::when($keyword, function ($query) use ($keyword) {
            return $query->where('broadcastTitle', 'ILIKE', "%$keyword%");
        })
            ->where('createdBy', $userId)
            ->where('campaignName', $campaignName)
            ->where('status', 'Archived')
            ->get();
        $detailsArray = $broadcasts->map(function ($broadcast, $currentPage) {
            $campaign_details = CampaignSetting::where('broadcastId', $broadcast->id)->first();
            $startDate = $campaign_details ? $campaign_details->startDate : null;
            $startTime = $campaign_details ? $campaign_details->startTime : null;
            $broadcastType = $campaign_details ? $campaign_details->broadcastType : null;

            return [
                'id' => $broadcast->id,
                'broadcastTitle' => $broadcast->broadcastTitle,
                'image' => $broadcast->image,
                'broadcastMessage' => $broadcast->broadcastMessage,
                'status' => $broadcast->status,
                'broadcastType' => $broadcastType,
                'startDate' => $startDate,
                'startTime' => $startTime,
                'currentPage' => $currentPage
            ];
        });
        $filteredArray = $detailsArray->filter(function ($broadcast) {
            return is_array($broadcast) && isset($broadcast['startDate']) && isset($broadcast['startTime']);
        })->values();

        return $filteredArray;
    }


}

