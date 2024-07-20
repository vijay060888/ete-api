<?php

namespace App\Helpers;

use App\Models\AlbumGallery;
use App\Models\AssignPartyToLeaders;
use App\Models\Leader;
use App\Models\LeaderAchievements;
use App\Models\LeaderAffidavit;
use App\Models\LeaderCoreParty;
use App\Models\LeaderElectionHistory;
use App\Models\LeaderFollowers;
use App\Models\LeaderMinistry;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\Volunteer;
use App\Models\Gallery;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Achievement;
use App\Models\NewsAndMedia;
use Auth;
use Crypt;

class LeaderProfileDetails
{
    public static function getLeaderProfileDetails($leaderId,$canEdit,$currentPage,$partyId,$showStream = true)
    {
    
            $userid = $leaderId;
            $user=User::find($leaderId);
            $userDetails = UserDetails::where('userId', $userid)->first();
            $leaderProfile = $userDetails != '' ? $userDetails->profileImage : '';
            $leaderDetails = Leader::where("leadersId", $userid)->select('descriptionShort','file', 'descriptionBrief', 'leaderBiography', 'leaderVision', 'leaderMission', 'officeAddress', 'about', 'social', 'timelineYear', 'timelineHeading', 'timelineDescriptions', 'file', 'followercount', 'voluntercount', 'phoneNumber2','contactPersonName','backgroundImage','leaderMinistry', 'leaderPartyRole as partylevelRoleName','leaderPartyRoleLevel as partyLevelRoleStateOrCenter')->first();
            $social = !empty($leaderDetails) ? json_decode($leaderDetails->social) : null;

            if ($leaderDetails === null) {
                $leaderDetails = [
                    'descriptionShort' => '',
                    'descriptionBrief' => '',
                    'leaderBiography' => '',
                    'leaderVision' => '',
                    'leaderMission' => '',
                    'officeAddress' => '',
                    'about' => '',
                    'social' => '',
                    'timelineYear' => '',
                    'timelineHeading' => '',
                    'timelineDescriptions' => '',
                    'file' => '',
                    'backgroundImage' =>'',
                    'contactPersonName' => '',
                    'followercount' => 0,
                    'voluntercount' => 0,
                    'partylevelRoleName' =>'',
                    'leaderPartyRoleLevel' =>'',
                    'partyLevelRoleStateOrCenter' => '',
                ];
            }
            $AssignLeader = AssignPartyToLeaders::where('leaderId', $leaderId)
            ->join('parties', 'parties.id', '=', 'assign_party_to_leaders.partyId')
            ->select('assign_party_to_leaders.status','assign_party_to_leaders.partyId', 'parties.name as partyName', 'parties.logo as logo', 'parties.type as type')
            ->get()
            ->map(function ($follower) {
                return $follower;
            });

        $leaderMinistries = LeaderMinistry::where('leaderId', $leaderId)
    ->join('ministries', 'ministries.id', '=', 'leader_ministries.ministryId')
    ->select('leader_ministries.status', 'ministries.ministryName','leader_ministries.type')
    ->get()
    ->map(function ($ministry) {
        return $ministry;
    });

    $leaderCoreParties = LeaderCoreParty::where('leaderId', $leaderId)
    ->join('parties', 'parties.id', '=', 'leader_core_parties.corePartyId')
    ->select('parties.name as partyName','parties.id as partyId')
    ->get()
    ->map(function ($party) {
        return $party;
    });


    $leaderElectionHistories = LeaderElectionHistory::where('leaderId', $leaderId)
    ->when(
        function ($query) {
            return !empty($query->orWhereNotNull('leader_election_histories.loksabhaId')->orWhereNotNull('leader_election_histories.assemblyId'));
        },
        function ($query) {
            $query->leftJoin('lok_sabha_consituencies', 'lok_sabha_consituencies.id', '=', 'leader_election_histories.loksabhaId')
                ->leftJoin('assembly_consituencies', 'assembly_consituencies.id', '=', 'leader_election_histories.assemblyId')
                ->leftJoin('parties', 'parties.id', '=', 'leader_election_histories.partyId')
                ->leftJoin('leaders', 'leaders.leadersId', '=', 'leader_election_histories.leaderId')
                ->select(
                    'leader_election_histories.electionHistoryLeaderResult',
                    'lok_sabha_consituencies.name as loksabhaname',
                    'assembly_consituencies.name as assemblyname',
                    'parties.name as partyName',
                    'parties.id as partyId',
                    'leaders.leaderMinistry'
                );
        }
    )
    ->get()
    ->map(function ($history) {
        return $history;
    });


            $leaderDetails['id'] = $userid;
            $leaderDetails['firstName'] = $user->firstName;
            $leaderDetails['lastName'] = $user->lastName;
            $isFollowing = LeaderFollowers::where('leaderId', $leaderId)->where('followerId',Auth::user()->id)->exists();
            $leaderDetails['isFollowing'] = $isFollowing;
            $affidavit = LeaderAffidavit::where('leadersId',$leaderId)->first();
            // $achievements = LeaderAchievements::where('leadersId',$leaderId)->select('acchievements','descriptions','durations','expenses')->get();
            $followercount = LeaderFollowers::where('leaderId', $leaderId)->count();
            $volunteercount = Volunteer::where('volunteersCreatedTypeId',$leaderId)->count();
            $leaderDetails['followercount'] =  $followercount;
            $leaderDetails['voluntercount'] =  $volunteercount;
            $leaderDetails['phoneNumber1'] = $user->phoneNumber;
            $leaderDetails['email'] = $user->email;
            $leaderDetails['leaderProfile'] = $leaderProfile;
            $leaderDetails['social'] = $social;
            $leaderDetails['requestFromParty'] = $AssignLeader;
            $leaderDetails['leaderMinistries'] = $leaderMinistries;
            $leaderDetails['leaderCoreParties'] = $leaderCoreParties;
            $leaderDetails['leaderElectionHistories'] = $leaderElectionHistories;
            $leaderDetails['educationUG'] = $user->educationUG;
            $leaderDetails['educationPG'] = $user->educationPG;
            $leaderDetails['affidavit'] = $affidavit ? $affidavit->affidavitFile : null;
            $leaderDetails['profesionalExperience'] = $user->profesionalExperience;
            // $leaderDetails['achievements'] = $achievements ? $achievements : null;
            $isUserYourSelf = Auth::user()->id == $userid;
            $leaderDetails['isUserYouSelf'] =  $isUserYourSelf;
            $leaderDetails['leaderBiography'] =  $leaderDetails->leaderBiography;
            
            $encryptedData = Crypt::encrypt([$leaderId,'Leader']);
            if($showStream == true){
                $stream = OptimizeFetchPost::getAllPost($currentPage,$partyId,null,null,null,$userid,null);
                $leaderDetails['stream'] = $stream;
            }
            $albumGallery = AlbumGallery::where('userId',$leaderId)->select('id','mediaURL' ,'mediaType')->get();
            $formattedMedia = $albumGallery->map(function ($item) {
                return [
                    'mediaType' => $item->mediaType,
                    'id' => $item->id,
                    'mediaurl' => $item->mediaURL,
                ];
            });
            
            $leaderDetails['media']=$formattedMedia;
            if($showStream == true){
                //for gallery starts
                $currentPagegall = request('page', 1);
                $perPagegall = "5";
                $galleriesQuery = Gallery::where('leader_id', $leaderId)
                                        ->whereIn('status', ['published'])
                                        ->get();
                $formattedGalleries = $galleriesQuery->map(function ($gallery) {
                    $media = json_decode($gallery->media, true); 
                    return [
                        'id' => $gallery->id,
                        'title' => $gallery->title,
                        'description' => $gallery->description,
                        'status' => $gallery->status,
                        'hashtag' => $gallery->hashtag,
                        'media' => $media,
                    ];
                });
                $desiredTotalgall = $formattedGalleries->count();
                $pagedPostsgall = $formattedGalleries->forPage($currentPagegall, $perPagegall)->values();
                $listgallery = new LengthAwarePaginator($pagedPostsgall, $desiredTotalgall, $perPagegall, $currentPagegall, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]);
                $leaderDetails['gallery'] = $listgallery;
                //for gallery ends

                //for achievements starts
                $currentPageachive = request('page', 1);
                $perPageachive = "5";
                $achivementsQuery = Achievement::where('leader_id', $leaderId)
                                        ->whereIn('status', ['published'])
                                        ->get();
                $formattedAchivements = $achivementsQuery->map(function ($achivements) {
                    $media = json_decode($achivements->mediaupload, true); 
                    return [
                        'id' => $achivements->id,
                        'title' => $achivements->achievementtitle,
                        'description' => $achivements->achievementdescription,
                        'costincured' => $achivements->costincured,
                        'status' => $achivements->status,
                        'hashtag' => $achivements->hashtag,
                        'url' => $achivements->url,
                        'media' => $media,
                    ];
                });
                $desiredTotalachieve = $formattedAchivements->count();
                $pagedPostsachive = $formattedAchivements->forPage($currentPageachive, $perPageachive)->values();
                $listachievements = new LengthAwarePaginator($pagedPostsachive, $desiredTotalachieve, $perPageachive, $currentPageachive, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]);
                $leaderDetails['achievements'] = $listachievements;
                //for achievements ends

                //for newsandmedai starts
                $currentPagenews = request('page', 1);
                $perPagenews = "5";
                $newsandmedaiQuery = NewsAndMedia::where('leader_id', $leaderId)
                                        ->whereIn('status', ['published'])
                                        ->get();
                $formattedNewsandMedia = $newsandmedaiQuery->map(function ($newsandmedia) {
                    $media = json_decode($newsandmedia->mediaupload, true); 
                    return [
                        'id' => $newsandmedia->id,
                        'title' => $newsandmedia->newsttitle,
                        'description' => $newsandmedia->newsdescription,
                        'costincured' => $newsandmedia->costincured,
                        'status' => $newsandmedia->status,
                        'hashtag' => $newsandmedia->hashtag,
                        'url' => $newsandmedia->url,
                        'media' => $media,
                    ];
                });
                $desiredTotalnews = $formattedNewsandMedia->count();
                $pagedPostsnews = $formattedNewsandMedia->forPage($currentPagenews, $perPagenews)->values();
                $listnews = new LengthAwarePaginator($pagedPostsnews, $desiredTotalnews, $perPagenews, $currentPagenews, [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ]);
                $leaderDetails['newsandmedia'] = $listnews;
                //for newsandmedai ends
                }
            

            return $leaderDetails;

       
    }
}
