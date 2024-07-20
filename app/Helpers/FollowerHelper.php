<?php

namespace App\Helpers;

use App\Models\CorePartyChangeRequest;
use App\Models\LeaderCoreParty;
use App\Models\PageRequest;
use App\Models\Party;
use App\Models\PartyFollowers;
use App\Models\LeaderFollowers;
use App\Models\PartyLogin;
use App\Models\PartyState;
use App\Models\State;
use App\Models\User;
use App\Models\UserDetails;
use Auth;


class FollowerHelper
{
    public static function getAllPageMagenementDetailsforLeader($userId)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);

        $partyFollowers = PartyFollowers::where('followerId', $userId)
            ->with(['party:id,name,logo'])
            ->get()
            ->map(function ($follower) {
                $stateCode = !empty($follower->party->getStateCode()) ? $follower->party->getStateCode() : '';
                return [
                    'followingId' => $follower->id,
                    'partyId' => $follower->party->id,
                    'name' => $follower->party->name,
                    'logo' => $follower->party->logo,
                    'stateCode' => $stateCode,
                ];
            });



        $leaderFollowers = LeaderFollowers::where('followerId', $userId)
            ->join('users', 'leader_followers.leaderId', '=', 'users.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->select(
                'leader_followers.id as leaderFollowingId',
                'users.id as leaderId',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->get()
            ->map(function ($follower) {
                return $follower;
            });

        $pageYouHaveAdminAccess = PartyLogin::where('userId', $userId)
            ->with(['party:id,name,logo,type'])
            ->paginate($perPage)
            ->map(function ($login) {
                $login->stateCode = !empty($login->party->getStateCode()) ? $login->party->getStateCode() : '';

                return [
                    'partyId' => $login->party->id,
                    'name' => $login->party->name,
                    'logo' => $login->party->logo,
                    'stateCode' => $login->stateCode,
                    'type' => $login->party->type
                ];
            });

        
        $pageRequests = PageRequest::where('requestedBy', $userId)
            ->with(['party:id,name,logo,type'])
            ->whereNull('partyName')
            ->select('page_requests.id as requestId','partyId', 'requestType', 'status', 'partyName', 'status')
            ->get()
            ->map(function ($pagerequest) {
                if ($pagerequest->party) {
                    $stateCode = $pagerequest->party->getStateCode();
                } else {
                    $stateCode = '';
                }
                
                return [
                    'requestId' => $pagerequest->requestId,
                    'name' => optional($pagerequest->party)->name,
                    'logo' => optional($pagerequest->party)->logo,
                    'stateCode' => $stateCode,
                    'requestType' => $pagerequest->requestType,
                    'type' => $pagerequest->type,
                    'status' => $pagerequest->status
                ];
            });

        $yourCurrentCoreParty = [];
        $coreParty = LeaderCoreParty::where('leaderId', Auth::user()->id)->first();
        if ($coreParty != '') {
            $party = Party::where('id', $coreParty->corePartyId)->select('id', 'name', 'logo')->get();
            $yourCurrentCoreParty = $party;
        }
        $corePartyChangeRequestData = CorePartyChangeRequest::where('leaderId', Auth::user()->id)->first();

        $otherCoreParty = LeaderCoreParty::where('leaderId', Auth::user()->id)->first();

        if ($corePartyChangeRequestData != '') {
            $otherCoreParty = Party::where('id', '<>', $otherCoreParty->corePartyId)->where('id', '<>', $corePartyChangeRequestData->partyId)->where('type', 'Center')->select('id', 'name', 'logo')->get();
        } else {
            if($otherCoreParty!='')
            {
               $otherCoreParty = Party::where('type', 'Center')->where('id', '<>', $otherCoreParty->corePartyId)->select('id', 'name', 'logo')->get(); 
            }
            

        }

        if ($corePartyChangeRequestData != '') {
            $corePartyChangeRequest = Party::where('id', $corePartyChangeRequestData->partyId)->select('id', 'name', 'logo')->get();
            foreach ($corePartyChangeRequest as $corePartyChangeRequests) {
                $corePartyChangeRequests->status = $corePartyChangeRequestData->status;
            }
        } else {
            $corePartyChangeRequest = [];
        }

        $followers = [
            'partyYouFollow' => $partyFollowers,
            'leaderYouFollow' => $leaderFollowers,
            'partyYouHaveAdminAccess' => $pageYouHaveAdminAccess,
            'yourPageRequest' => $pageRequests,
            'yourCurrentCoreParty' => $yourCurrentCoreParty,
            'otherCoreParty' => $otherCoreParty,
            'corePartyChangeRequest' => $corePartyChangeRequest
        ];

        return $followers;
    }



    public static function getPartyFollowersForUser($userId)
    {

        $partyFollowers = PartyFollowers::where('followerId', $userId)
            ->with('party')
            ->get();

    }

}



?>