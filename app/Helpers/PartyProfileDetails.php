<?php

namespace App\Helpers;

use App\Models\AlbumGallery;
use App\Models\Party;
use App\Models\PartyFollowers;
use Auth;
use Crypt; 

class PartyProfileDetails
{
    public static function getPartyDetails($currentPage,$partyId,$canEdit,$viewPartyId)
    {
       
        $party = Party::find($partyId);

        $party = Party::where('id', $partyId)
            ->select('id', 'file', 'social', 'logo', 'backgroundImage', 'nameAbbrevation', 'name', 'followercount', 'voluntercount','type')
            ->first();
        
        if ($party) {
           //about
            $party['about'] = $party->getAboutDetails()->about ?? '';
            $party['vision'] = $party->getAboutDetails()->vision ?? '';
            $party['mission'] = $party->getAboutDetails()->mission ?? '';
            //timeline
            $isFollowing = PartyFollowers::where('partyId', $partyId)->where('followerId',Auth::user()->id)->exists();
            $party['isFollowing'] = $isFollowing;
            $party['timelineYear'] = $party->getPartyTimeLine()->year ?? '';
            $party['timelineHeading'] = $party->getPartyTimeLine()->heading ?? '';
            $party['timelineDescriptions'] = $party->getPartyTimeLine()->descriptions ?? '';
            //Contact Details 
            $party['contactName'] = $party->getPartyContact()->contactName ?? '';
            $party['phoneNumber'] = $party->getPartyContact()->phoneNumber ?? '';
            $party['phoneNumber2'] = $party->getPartyContact()->phoneNumber ?? '';
            $party['email'] = $party->getPartyContact()->email ?? '';
            $party['officeAddress'] = $party->getPartyContact()->officeAddress ?? '';
            $party['social'] = !empty($party) ? json_decode($party->social) : [];
            $party['followercount'] = PartyFollowers::where('partyId',$partyId)->count();

            if($canEdit=="True"){
            $party['type'] = $party->type;
            // $party['pageRequest'] = $party->getCreatePageRequest() ?? [];
            $party['createPageRequest'] = $party->getCreatePageRequestOnlyCreate() ?? [];
            $party['accessPageRequest'] = $party->getAccessPageRequestOnlyAccess() ?? [];
            $party['statePages'] = $party->getPartyNamesByState() ?? [];
            $party['assemblyPages'] = $party->getPartyNamesByStateAndAssembly() ?? [];
            $party['loksabhaPages'] = $party->getLokSabhaNames() ?? [];    
            $party['leaderListToAssign'] = $party->changeRequestToLeaderForAccess();
            }
            // $party['social'] =PartySocial::where('partyId','9a2d85dd-15cf-4f6e-9e6d-4202ec2817ee')->select('option','value')->get();


        } else {
            $party = [
                'id' => '',
                'statePages' => [],
                'loksabhaPages' => [],
                'assemblyPages' => [],
                'about' => '',
                'vision' => '',
                'mission' => '',
                'timelineYear' => '',
                'timelineHeading' => '',
                'timelineDescriptions' => '',
                'file' => '',
                'phonenumber2' => '',
                'phoneNumber' => '',
                'email' => '',
                'social' => [],
                'logo' => '',
                'backgroundImage' => '',
                'nameAbbrevation' => '',
                'name' => '',
                'contactName' => '',
                'pageRequest' => '',
                'type' =>'',
            ];
        }
        $encryptedData = Crypt::encrypt([$partyId,'Party']);
        
        // $stream = FetchAllPost::getAllPost($currentPage,$viewPartyId,null,null,null,$encryptedData,null);
        $stream = OptimizeFetchPost::getAllPost($currentPage,$viewPartyId,null,null,null,$partyId,null);

        $albumGallery = AlbumGallery::where('userId',$partyId)->select('id','mediaURL' ,'mediaType')->get();
        $formattedMedia = $albumGallery->map(function ($item) {
            return [
                'mediaType' => $item->mediaType,
                'id' => $item->id,
                'mediaurl' => $item->mediaURL,
            ];
        });
        
        $party['media']=$formattedMedia;

        $party['stream'] = $stream;
        return $party;
    }


}




?>