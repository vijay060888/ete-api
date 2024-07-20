<?php
namespace App\Helpers;

use App\Models\AssemblyFollowerDetails;
use App\Models\StateDetails;
use App\Models\AssemblyDetails;
use App\Models\LokSabhaDetails;
use App\Models\AssemblyConsituency;
use App\Models\Broadcast;
use App\Models\BroadcastTarget;
use App\Models\LokSabhaConsituency;
use App\Models\CampaignCredit;
use App\Models\CampaignSetting;
use App\Models\Notification;
use App\Models\SMSTemplate;
use App\Models\User;
use App\Models\State;
use App\Models\UserFollowerTag;
use App\traits\WhatsAppTrait;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EstimationReach {

    use WhatsAppTrait;

    public static function getReachInfo($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy)
    {
        $gender_filter = function($query) use ($gender) {
            $query->where('gender', $gender);
        };
 
        if ($constituencyId) {
            $constituencyType = self::getConstituencyType($constituencyId);
            $type = $constituencyType['type'];
            $consituencyDetails = ($type === "Assembly") ? \App\Models\AssemblyConsituency::class :  \App\Models\LokSabhaConsituency::class;

            $consituencyDetails = $consituencyDetails::where('id', $constituencyId)->first();

            $model = ($type === "Assembly") ? \App\Models\AssemblyDetails::class :  \App\Models\LoksabhaDetails::class;
            $consituencyId = ($type === "Assembly") ? "assemblyId" :  "loksabhaId";

            $query = $model::where("$consituencyId",  $consituencyDetails->id);
            if ($type == "Assembly") {
                $query->where('assemblyId', $constituencyId);
            } else {
                $query->where('loksabhaId', $constituencyId);
            }
    
            if ($gender) {
                $query->where($gender_filter);
            }
    
            if ($minAge && $maxAge && $minAge < $maxAge) {
                $existData = $query->get();
                $reachCount = 0;
                foreach ($existData as $key=>$data) {
                    $ageArr = json_decode($data->ageRange, true);
                    $totalAgeofGenderCount = 0;
                    if($ageArr!="")
                    {
                    foreach ($ageArr as $ageCat => $ageCount) {
                        if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                            $totalAgeofGenderCount += $ageCount;
                        }
                    }    
                }
                    $reachCount += $totalAgeofGenderCount;
                }
            } else {
                $reachCount = $query->sum('user_count');
            }

            $followerCount = 0;
            $getConstituencyFollower = AssemblyFollowerDetails::where('assemblyId', $constituencyId);
            if ($gender) {
                $getConstituencyFollower->where('gender', $gender);
            }
            
            if ($minAge && $maxAge && $minAge < $maxAge) {
                $existData = $getConstituencyFollower->get();
                foreach ($existData as $key => $data) {
                    $ageArr = json_decode($data->ageRange, true);
                    $totalAgeofGenderCount = 0;
                    if($ageArr!="")
                    {
                        foreach ($ageArr as $ageCat => $ageCount) {
                            if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                                $totalAgeofGenderCount += $ageCount;
                            }
                        }
                    }
                    $followerCount += $totalAgeofGenderCount;
                }
            } else {
                $followerCount = $getConstituencyFollower->sum('follower_count');
            }

            // Adding Follower Reach Count
                $reachCount += $followerCount;
        } else {
            $query = StateDetails::where('stateId', $stateId);       
            if ($gender != "") {
                $query->where($gender_filter);
            }

            if ($minAge && $maxAge && $minAge < $maxAge) {
                $existData = $query->get();
                $reachCount = 0;
                foreach ($existData as $key=>$data) {
                    $ageArr = json_decode($data->ageRange, true);
                    $totalAgeofGenderCount = 0;
                    if(is_array($ageArr))
                    {
                    foreach ($ageArr as $ageCat => $ageCount) {
                        if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                            $totalAgeofGenderCount += $ageCount;
                        }
                    }
                }
                    $reachCount += $totalAgeofGenderCount;  
                }
            } else {
                $reachCount = $query->sum('user_count');
            }
        }
        
        $costperuser = 1;
        $campaignCredit = CampaignCredit::where('assignedTo', $createdBy)->first();
 
        if ($campaignCredit) {
            $availableCredit = $campaignCredit->credits;
        } else {
            $availableCredit = 0;
        }        
        $requiredCredit = $reachCount * $costperuser;
        $confirmedReachCount = $availableCredit / $costperuser;
        if($availableCredit > $requiredCredit){
            $confirmedReachCount = $reachCount;
        }
        $data = [
            'estimatedReach' => $reachCount,
            'confirmedReach' =>  $confirmedReachCount,
            'availableCredit' => $availableCredit,
            'requiredCredit' => $requiredCredit,
        ];
 
        return $data;
    }


    // =================================================================== //
    //                   Broadcast Changes For Schedule Block              //
    // =================================================================== //

    public function getBroadcastReachCount($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy)
    {
        $reachCount = 0;
        if ($constituencyId) {
            // Gets count of users Belongs to Constituency
            $reachCount+= $this->getUserBelongsToConstituencyCount($constituencyId,  $constituencyId, $minAge, $maxAge, $gender);

            // Gets Count of Users Following Constituency
            $reachCount+= $this->getUserFollowsConstituencyCount($constituencyId,  $constituencyId, $minAge, $maxAge, $gender);
        } else {
            // gets Count of Users in State
            $reachCount += $this->getUsersFromStateCount($stateId,  $constituencyId, $minAge, $maxAge, $gender);
        }
        
        $costperuser = 1;
        $campaignCredit = CampaignCredit::where('assignedTo', $createdBy)->first();
 
        if ($campaignCredit) {
            $availableCredit = $campaignCredit->credits;
        } else {
            $availableCredit = 0;
        }        
        $requiredCredit = $reachCount * $costperuser;
        $confirmedReachCount = $availableCredit / $costperuser;
        if($availableCredit > $requiredCredit){
            $confirmedReachCount = $reachCount;
        }
        $data = [
            'estimatedReach' => $reachCount,
            'confirmedReach' => $confirmedReachCount,
            'availableCredit' => $availableCredit,
            'requiredCredit' => $requiredCredit,
        ];
        return $data;
    }

    function getUserBelongsToConstituencyCount($constituencyId, $gender, $minAge, $maxAge) {
        $constituencyType = self::getConstituencyType($constituencyId);
        $type = $constituencyType['type'];
        $consituencyDetails = ($type === "Assembly") ? \App\Models\AssemblyConsituency::class :  \App\Models\LokSabhaConsituency::class;

        $consituencyDetails = $consituencyDetails::where('id', $constituencyId)->first();

        $model = ($type === "Assembly") ? \App\Models\AssemblyDetails::class :  \App\Models\LoksabhaDetails::class;
        $consituencyId = ($type === "Assembly") ? "assemblyId" :  "loksabhaId";

        $query = $model::where("$consituencyId",  $consituencyDetails->id);
        if ($type == "Assembly") {
            $query->where('assemblyId', $constituencyId);
        } else {
            $query->where('loksabhaId', $constituencyId);
        }
   
        if ($gender) {
            $query->where('gender', $gender);
        }
   
        if ($minAge && $maxAge && $minAge < $maxAge) {
            $existData = $query->get();
            $reachCount = 0;
            foreach ($existData as $key=>$data) {
                $ageArr = json_decode($data->ageRange, true);
                $totalAgeofGenderCount = 0;
                if($ageArr!="")
                {
                foreach ($ageArr as $ageCat => $ageCount) {
                    if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                        $totalAgeofGenderCount += $ageCount;
                    }
                }    
            }
                $reachCount += $totalAgeofGenderCount;
            }
        } else {
            $reachCount = $query->sum('user_count');
        }
        return $reachCount;
    }

    function getUserFollowsConstituencyCount($constituencyId, $gender, $minAge, $maxAge) {
        $followerCount = 0;
        $getConstituencyFollower = AssemblyFollowerDetails::where('assemblyId', $constituencyId);
        if ($gender) {
            $getConstituencyFollower->where('gender', $gender);
        }
        
        if ($minAge && $maxAge && $minAge < $maxAge) {
            $existData = $getConstituencyFollower->get();
            foreach ($existData as $key => $data) {
                $ageArr = json_decode($data->ageRange, true);
                $totalAgeofGenderCount = 0;
                if($ageArr!="")
                {
                    foreach ($ageArr as $ageCat => $ageCount) {
                        if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                            $totalAgeofGenderCount += $ageCount;
                        }
                    }
                }
                $followerCount += $totalAgeofGenderCount;
            }
        } else {
            $followerCount = $getConstituencyFollower->sum('follower_count');
        }

        return $followerCount;
    }

    function getUsersFromStateCount($stateId, $gender, $minAge, $maxAge) {
        $reachCount = 0;
        $query = StateDetails::where('stateId', $stateId);
       
        if ($gender != "") {
            $query->where('gender', $gender);
        }
   
        if ($minAge && $maxAge && $minAge < $maxAge) {
            $existData = $query->get();
            $reachCount = 0;
            foreach ($existData as $key=>$data) {
                $ageArr = json_decode($data->ageRange, true);
                $totalAgeofGenderCount = 0;
                if(is_array($ageArr))
                {
                foreach ($ageArr as $ageCat => $ageCount) {
                    if ($ageCat >= floor($minAge / 10) * 10 && $ageCat <= floor($maxAge / 10) * 10) {
                        $totalAgeofGenderCount += $ageCount;
                    }
                }
            }
                $reachCount += $totalAgeofGenderCount;  
            }
        } else {
            $reachCount = $query->sum('user_count');
        }
        return $reachCount;
    }

    function triggerBroadCastMessage($broadcastId) {
        try {
            $broadcastDetails = Broadcast::findOrFail($broadcastId);
    
            $message = $broadcastDetails->broadcastMessage;
            $title = $broadcastDetails->broadcastTitle;
            $createdBy = $broadcastDetails->createdBy;
            $senderType = $broadcastDetails->createdByType;
            $smsId = $broadcastDetails->smsId ?? null;

            $broadcastTarget = BroadcastTarget::where('broadcastId', $broadcastId)->firstOrFail();
            $stateId = $broadcastTarget->stateId;
            $constituencyId = $broadcastTarget->constituencyId;
            $minAge = $broadcastTarget->minAge;
            $maxAge = $broadcastTarget->maxAge;
            $gender = $broadcastTarget->gender;
    
            $campaignSetting = CampaignSetting::where('broadcastId', $broadcastId)->firstOrFail();
            $broadcastType = $campaignSetting->broadcastType;

            // Retrieve reach information
            $data = EstimationReach::getReachInfo($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy);
            $confirmReached = $data['confirmedReach'];
            $pluckType = ($broadcastType == 'whatsapp') ? "phone" : null;

            $userIds = EstimationReach::getUserDetails($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy, $confirmReached, $pluckType);

            // For Testing purpose
            // Mail::raw(implode(" ", $userIds), function($email) {
                //     $email->to('ktkkishore95@gmail.com')->subject('Sample Mail');
            // });

            if ($broadcastType == "notification") {
                foreach ($userIds as $userId) {
                    $user = User::find($userId);
                    $userType = $user->getRoleNames()[0];
                    
                    $createNotificationData = [
                        'type' => $userType,
                        'typeId' => $user->id,
                        'isBroadcast' => true,
                        'isRead' => 0,
                        'userId' => $createdBy,
                        'notificationMessage' => $title,
                        'createdBy' => Auth::user()->id,
                        'updatedBy' => Auth::user()->id,
                        'userType' => $senderType,
                        'broadcast_id' => $broadcastId,
                        'notificationtype' => "Broadcast",
                        'notificationtypeid' => $broadcastId,
                        'notificationtypecategory' => $senderType,
                    ];
                    
                    Notification::create($createNotificationData);
                }
            } elseif ($broadcastType == "sms") {
                $smsTemplate = SMSTemplate::find($smsId);
                if (!$smsTemplate) {
                    return response()->json(['status' => 'error', 'message' => 'You have not selected any SMS template'], 400);
                }
                
                $smsType = ucfirst(strtolower($smsTemplate->SMSType));
                $getTemplate = Action::getSMSTemplate($smsType);
                if ($getTemplate) {
                    foreach ($userIds as $userId) {
                        $user = User::find($userId);
                        $fullName = $user->firstName . " " . $user->lastName;
                        
                        foreach ($getTemplate['data'] as $smsData) {
                            $content = str_replace("{#var#}", $fullName, $smsData->template);
                            $details = [
                                'template' => $content,
                                'tempid' => $smsData->tempid,
                                'entityid' => $smsData->entityid,
                                'source' => $smsData->source
                            ];

                            Action::sendSMS($details, $user->phoneNumber);
                        }
                    }
                }
            }else if ($broadcastType == 'whatsapp') {
                Log::info($userIds);
                $phoneNumbers = array_unique($userIds);
                $templateName = $campaignSetting->whTemplate ?? "promo2";
                $response = EstimationReach::sendWhatsappNotification($templateName, $phoneNumbers);
                if ($response['status'] != 'success') {
                    return response()->json(['status'=> false, 'error'=> $response['data']], 500);   
                }
                
            }

            $broadcastDetails->update(['status' => 'Active']);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =================================================================== //
    //                Broadcast Changes For Schedule Block End             //
    // =================================================================== //


    public static function getAdReachInfo($stateId, $constituencyId, $createdBy, $startDate, $endDate,$startTime,$endTime)
    {
        if ($constituencyId) {
            $constituency = self::getConstituencyType($constituencyId);
            $constituencyType = $constituency['type'];
            $consituencyDetails = ($constituencyType === "Assembly") ? \App\Models\AssemblyConsituency::class :  \App\Models\LokSabhaConsituency::class;

            $consituencyDetails = $consituencyDetails::where('id', $constituencyId)->first();
            
            $model = ($constituencyType === "Assembly") ? \App\Models\AssemblyDetails::class :  \App\Models\LoksabhaDetails::class;
            $consituencyId = ($constituencyType === "Assembly") ? "assemblyId" :  "loksabhaId";

            $query = $model::where("$consituencyId",  $consituencyDetails->id);
            
            if ($constituencyType == "Assembly") {
                $query->where('assemblyId', $constituencyId);
            } else {
                $query->where('loksabhaId', $constituencyId);
            }
            $reachCount = $query->sum('user_count'); 

        } else {
            $query = StateDetails::where('stateId', $stateId);
            $reachCount = $query->sum('user_count');
        }
        $campaignDurationDays = '';

        if($startDate!='' && $endDate!='' && $startTime!='' && $endTime!=''){
            $startDateTime = Carbon::parse($startDate . ' ' . $startTime);
            $endDateTime = Carbon::parse($endDate . ' ' . $endTime);
            $campaignDurationDays = $endDateTime->diffInDays($startDateTime);
        }
        
        $costperuser = 1;
        $availableCredit = CampaignCredit::where('assignedTo', $createdBy)->first();
        
        if ($availableCredit) {
            $availableCredit = $availableCredit->credits;
        } else {
            $availableCredit = 0;
        }   
        $requiredCredit = $reachCount * $costperuser;
        $confirmedReachCount = $availableCredit / $costperuser;

        if($campaignDurationDays!='' && $campaignDurationDays!=0)
        {
            $confirmedReachCount = min($availableCredit / $costperuser, $reachCount);
            $requiredCredit = $reachCount * $costperuser * $campaignDurationDays;
        }

        if($availableCredit > $requiredCredit){
            $confirmedReachCount = $reachCount;
        }
        else{
             
            $confirmedReachCount = ceil($reachCount * ($availableCredit / $requiredCredit));
        }
        $data = [
            'estimatedReach' => $reachCount,
            'confirmedReach' =>  $confirmedReachCount,
            'availableCredit' => $availableCredit,
            'requiredCredit' => $requiredCredit,
        ];

        return $data;
    }

    public static function getUserDetails($stateId, $constituencyId, $minAge, $maxAge, $gender, $createdBy, $confirmReached, $pluckType = null)
    {
        $usersQuery = User::role(['Leader', 'Citizen']);
        $state = State::find($stateId);
        $state_name =$state->name;
        if ($constituencyId == "") {
            $usersQuery->join('user_addresses', function ($join) use ($state_name) {
            $join->on('users.id', '=', 'user_addresses.userId')
                ->where('user_addresses.state', '=', $state_name);
            })
            ->join('user_details', 'users.id', '=', 'user_details.userId');
        } else {
            $constituency = self::getConstituencyType($constituencyId);
            $constituencyType = $constituency['type'];

            $usersQuery->join('user_details', 'users.id', '=', 'user_details.userId');
            $usersQuery = ($constituencyType == "Assembly") ? $usersQuery->where("user_details.assemblyId", '=', $constituencyId) : $usersQuery->where("user_details.loksabhaId", '=', $constituencyId);
            
        }
    
        if ($gender != "") {
            $usersQuery->where('gender', $gender);
        }
    
        if ($minAge != "" && $maxAge != "") {
            $usersQuery->whereRaw('EXTRACT(YEAR FROM AGE(TO_DATE("DOB", \'DD-MM-YYYY\'))) BETWEEN ? AND ?', [$minAge, $maxAge]);
        }
        $usersQuery->limit($confirmReached);

        $followerUserids = [];
        if($constituencyId) {
            $followedTagsUserIds =  UserfollowerTag::where('followedTags', $constituency['detail']->name)
                                    ->whereHas('user', function ($query) {
                                        $query->role(['Leader', 'Citizen']);
                                    })->pluck('userId')->toArray();
            $userFollowerIds = User::whereIn('id', $followedTagsUserIds);
            if ($gender != "") {
                $userFollowerIds->where('gender', $gender);
            }
        
            if ($minAge != "" && $maxAge != "") {
                $userFollowerIds->whereRaw('EXTRACT(YEAR FROM AGE(TO_DATE("DOB", \'DD-MM-YYYY\'))) BETWEEN ? AND ?', [$minAge, $maxAge]);
            };
            $followerUserids = $userFollowerIds->pluck('id')->toArray();
        }
        
        if($pluckType == "phone"){
            $user = $usersQuery->pluck('phoneNumber')->toArray();
            if ($followerUserids) {
                $userFollowersPhoneNumber = User::whereIn('id', $followerUserids)->pluck('phoneNumber');
                if(count($userFollowersPhoneNumber) > 0) {
                    $user = array_unique(array_merge($user, $userFollowersPhoneNumber));
                }
            }
        } else {
            $user = $usersQuery->pluck('users.id')->toArray();
            if (count($followerUserids) > 0) {
                $user = array_unique(array_merge($user, $followerUserids));
            }
        }
        
        // Testing Purpose
        // $userName = User::whereIn('id',$user)->pluck('userName')->toArray();
        // ->select("userName", "phoneNumber")->get();
        // $userName = User::whereIn('id',$user)->pluck("phoneNumber");
        // return $userName;

        return $user;
    }

    public static function getConstituencyType($constituencyId)
    {
        $assemblyExists = AssemblyConsituency::where('id', $constituencyId)->first();
        $loksabhaExists = LokSabhaConsituency::where('id', $constituencyId)->first();
    
        if ($assemblyExists && $loksabhaExists) {
            return null;
        } elseif ($assemblyExists) {
            return [
                'detail' => $assemblyExists,
                'type' => 'Assembly'
            ];
        } elseif ($loksabhaExists) {
            return [
                'detail' => $loksabhaExists,
                'type' => 'Loksabha'
            ];
        }
    }

    
    public static function sendWhatsappNotification($templateName, $phoneNumbers) {
        $integratedNumber = "918970066999";
        $toAndComponents = [];
        $senders =  User::whereIn('phoneNumber', $phoneNumbers)
                    ->select('firstName', 'lastName', 'phoneNumber')
                    ->groupBy('firstName', 'lastName', 'phoneNumber')->get();
        foreach($senders as $user) {
            $toAndComponents[] = [
                "to" => ['91'.$user->phoneNumber],
                "components" => [
                    "body_1" => [
                        "type" => "text",
                        "value" => $user->firstName.' '.$user->lastName
                    ]
                ]
            ];
        }
        $instance = new self();
        $templateResult = $instance->sendWhatsappMsg($integratedNumber, $templateName, $toAndComponents);       
        return $templateResult;
    }
}