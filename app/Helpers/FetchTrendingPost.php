<?php


namespace App\Helpers;

use App\Models\LeaderFollowers;
use App\Models\Likes;
use App\Models\PartyFollowers;
use App\Models\PollsByCitizenDetails;
use App\Models\PollsByLeaderDetails;
use App\Models\PollsByPartyDetails;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use App\Models\User;
use Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchTrendingPost
{
    public static function getAllPostsFromLeaders($limit, $offset,$partyId,$trendingHashtags,$currentPage)
    {
        $combinedPosts = PostByLeader::with([
            'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
            'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
            'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
            'user',
            'user.userDetails',
        ])
        ->where('hashTags','like',"%$trendingHashtags%")
        ->orderBy('createdAt','desc')
        ->take($limit)
        ->offset($offset)
        ->get();
     
        $formattedLeaderPosts = $combinedPosts->map(function ($post) use ($currentPage,$partyId) {
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $poll = PollsByLeaderDetails::where('postByLeaderId', $post->id)->first();
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';

            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }
            $rolename = !is_null($partyId) ? 'Party' : Auth::user()->getRoleNames()[0];
            $postByFirstName = $post->user->firstName;
            $postByLastName = $post->user->lastName;
            $postByFullName = $postByFirstName . " " . $postByLastName;
            $likes = new Likes();
            $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
            $isLiked = $hasLiked ? true : false;

            $formattedDate = $post->createdAt->diffForHumans();
            $imageUrls = [
                optional($post->postByLeaderMetas)->first()->imageUrl1 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl2 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl3 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl4 ?? null,
            ];
            $imageUrls = array_filter($imageUrls);

            $optionCounts = $post->pollsByLeaderDetails->pluck('optionCount')->toArray();
            $totalSum = array_sum($optionCounts);

            if ($totalSum !== 0) {
                $percentages = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $percentages[] = $percentage;
                }
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }
            $isEditable = $post->user->id === Auth::user()->id;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url =  env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url .'/sharedPost/'. $encryptedPostId;
            $isOwnPost = $userId == $post->leaderId;
            $following = LeaderFollowers::where("followerId", Auth::user()->id)
            ->where("leaderId", $post->leaderId)
            ->exists();

            return [
                'postURL' =>  $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => $post->user->userName,
                'postByProfilePicture' => !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null,
                'isLiked' => $isLiked,
                'likedType' => $hasLiked,
                'authorType' => $post->authorType,
                'leaderId' => $post->leaderId,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'ideaDepartment' => $post->postByLeaderMetas->pluck('ideaDepartment')->first(),
                'postDescriptions' => $post->postByLeaderMetas->pluck('postDescriptions')->first(),
                'image' => $imageUrls,
                'pollOption' => $post->pollsByLeaderDetails->pluck('pollOption')->toArray(),
                'optionCount' => $percentages,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => $post->postByLeaderMetas->pluck('PollendDate')->first(),
                'pollendTime' => $post->postByLeaderMetas->pluck('pollendTime')->first(),
                'complaintLocation' => $post->postByLeaderMetas->pluck('complaintLocation')->first(),
                'optionLength' => count($post->pollsByLeaderDetails),
                'eventsLocation' => $post->eventsByLeader->pluck('eventsLocation')->first(),
                'eventStartDate' => $post->eventsByLeader->pluck('startDate')->first(),
                'eventsEndDate' => $post->eventsByLeader->pluck('endDate')->first(),
                'eventStartTime' => $post->eventsByLeader->pluck('startTime')->first(),
                'eventsEndTime' => $post->eventsByLeader->pluck('endTime')->first(),
                'IsFollowing' => $following,
                'IsEditable' => $isEditable,
                'postCreatedAt' => $formattedDate,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' =>$isOwnPost,
                'createdBy' => $post->leaderId,

            ];
        });


        return $formattedLeaderPosts;
    }

    public static function getAllPostsFromParty($limit, $offset,$partyId,$trendingHashtags,$currentPage)
    {

    $userId = Auth::user()->id;

    $partyPosts = PostByParty::where('anonymous', false)->join('parties', 'parties.id', '=', 'post_by_parties.partyId')

        ->with([
            'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
            'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
            'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
        ])
        ->where('post_by_parties.hashTags','like',"%$trendingHashtags%")
        ->take($limit)
        ->offset($offset)
        ->get();


        $formattedPartyPosts = $partyPosts->map(function ($post) use ($partyId,$currentPage) {
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';
            $poll = PollsByPartyDetails::where('postByPartyId', $post->id)->first();

            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }

            $likes = new Likes();
            $rolename = !is_null($partyId) ? 'Party' : Auth::user()->getRoleNames()[0];
            $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
            $isLiked = $hasLiked ? true : false;
            $formattedDate = $post->createdAt->diffForHumans();
            $imageUrls = [
                optional($post->postByPartyMetas)->first()->imageUrl1 ?? null,
                optional($post->postByPartyMetas)->first()->imageUrl2 ?? null,
                optional($post->postByPartyMetas)->first()->imageUrl3 ?? null,
                optional($post->postByPartyMetas)->first()->imageUrl4 ?? null,
            ];

            $imageUrls = array_filter($imageUrls);

            $optionCounts = $post->pollsByPartyDetails->pluck('optionCount')->toArray();
            $totalSum = array_sum($optionCounts);

            if ($totalSum !== 0) {
                $percentages = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $percentages[] = $percentage;
                }
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }
            $isEditable = ($partyId !== '') ? ($post->partyId === $partyId) : false;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url =  env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url .'/sharedPost/'. $encryptedPostId;

            $following = PartyFollowers::where("followerId", Auth::user()->id)
            ->where("partyId", $post->partyId)
            ->exists();
            $isOwnPost = $partyId === $post->partyId;
            return [
                'postURL' =>  $postURL,
                'postId' => $post->id,
                'postByName' => $post->name,
                'postByUserName' => null,
                'postByProfilePicture' => $post->logo,
                'isLiked' => $isLiked,
                'likedType' => $hasLiked,
                'authorType' => $post->authorType,
                'partyId' => $post->partyId,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'ideaDepartment' => $post->postByPartyMetas->pluck('ideaDepartment')->first(),
                'postDescriptions' => $post->postByPartyMetas->pluck('postDescriptions')->first(),
                'image' => $imageUrls,
                'pollOption' => $post->pollsByPartyDetails->pluck('pollOption')->toArray(),
                'optionCount' => $percentages,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => optional($post->postByPartyMetas)->first()?->PollendDate,
                'pollendTime' => optional($post->postByPartyMetas)->first()?->pollendTime,
                'complaintLocation' => optional($post->postByPartyMetas)->first()?->complaintLocation,
                'optionLength' => count($post->pollsByPartyDetails),
                'eventsLocation' => optional($post->eventsByParty)->first()?->eventsLocation,
                'eventStartDate' => optional($post->eventsByLeader)->first()?->startDate,
                'eventsEndDate' => optional($post->eventsByLeader)->first()?->endDate,
                'eventStartTime' => optional($post->eventsByLeader)->first()?->startTime,
                'eventsEndTime' => optional($post->eventsByLeader)->first()?->endTime,
                'IsFollowing' => $following,
                'postCreatedAt' => $formattedDate,
                'IsEditable' => $isEditable,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' =>  $isOwnPost,
                'createdBy' => $post->partyId,



            ];
        });

        return $formattedPartyPosts;
    }


    public static function getAllPostsFromCitizens($limit, $offset,$partyId,$trendingHashtags,$currentPage)
    {
        $userId = Auth::user()->id;
        // $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
    
        $citizensPosts = PostByCitizen::where('anonymous', false)
            ->with([
                'postByCitizenMetas:id,postByCitizenId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByCitizenDetails:id,postByCitizenId,pollOption,optionCount',
                'pollsByCitizenDetails.pollsByCitizenVotes:id,pollsByCitizenDetailsId',
                'user',
                'user.userDetails'
            ])
            ->where('hashTags','like',"%$trendingHashtags%")
            ->take($limit)
            ->offset($offset)
            ->get();

        $formattedCitizenPosts = $citizensPosts->map(function ($post) use($currentPage,$partyId){
            $userId = empty($partyId) ? Auth::user()->id : $partyId;
            $poll = PollsByCitizenDetails::where('postByCitizenId', $post->id)->first();
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';
            
            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }
            $rolename = !is_null($partyId) ? 'Party' : Auth::user()->getRoleNames()[0];
            $postByFirstName = $post->user ? $post->user->firstName : null;
            $postByLastName = $post->user ? $post->user->lastName : null;
            $postByFullName = $postByFirstName . " " . $postByLastName;
            $likes = new Likes();
            $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
            $isLiked = $hasLiked ? true : false;
            $formattedDate = $post->createdAt->diffForHumans();
            $imageUrls = [
                optional($post->postByCitizenMetas)->first()->imageUrl1 ?? null,
                optional($post->postByCitizenMetas)->first()->imageUrl2 ?? null,
                optional($post->postByCitizenMetas)->first()->imageUrl3 ?? null,
                optional($post->postByCitizenMetas)->first()->imageUrl4 ?? null,
            ];
            $imageUrls = array_filter($imageUrls);
            $optionCounts = $post->pollsByCitizenDetails->pluck('optionCount')->toArray();
            $totalSum = array_sum($optionCounts);

            if ($totalSum !== 0) {
                $percentages = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $percentages[] = $percentage;
                }
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }
     
            $isEditable = $post->user->id === Auth::user()->id;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url =  env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url .'/sharedPost/'. $encryptedPostId;
            $isOwnPost = Auth::user()->id === $post->citizenId;

            return [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => $post->user->userName,
                'postByProfilePicture' => !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null,
                'isLiked' => $isLiked,
                'likedType' => $hasLiked,
                'authorType' => $post->authorType,
                'citizenId' => $post->citizenId,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'ideaDepartment' => optional($post->postByCitizenMetas)->first()->ideaDepartment ?? null,
                'postDescriptions' => optional($post->postByCitizenMetas)->first()->postDescriptions ?? null,
                'image' => $imageUrls,
                'pollOption' => optional(optional($post->pollsByCitizenDetails)->pluck('pollOption'))->toArray(),
                'optionCount' => $percentages,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => optional($post->postByCitizenMetas)->first()->PollendDate ?? null,
                'pollendTime' => optional($post->postByCitizenMetas)->first()->pollendTime ?? null,
                'complaintLocation' => optional($post->postByCitizenMetas)->first()->complaintLocation ?? null,
                'optionLength' => optional($post->pollsByCitizenDetails)->count() ?? 0,
                'eventsLocation' => null,
                'eventStartDate' => null,
                'eventsEndDate' => null,
                'eventStartTime' => null,
                'eventsEndTime' => null,
                'IsFollowing' => false,
                'IsEditable' => $isEditable,
                'postCreatedAt' => $formattedDate,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' =>   $isOwnPost ,
                'createdBy' => $post->citizenId,


            ];
        });

        return $formattedCitizenPosts;
    }



    public static function getAllPost($currentPage,$partyId,$trendingHashtags)
    {
        $sources = [
            'getAllPostsFromParty',
            'getAllPostsFromCitizens',
            'getAllPostsFromLeaders',

        ]; 
       $limit = env('MAX_POSTS_PER_SOURCE', 300);

        $combinedPosts = collect([]);
        $perPage = 5;
        $sourceData = [];
        foreach ($sources as $source) {
            $sourceData[$source] = self::$source($limit, 0,$partyId,$trendingHashtags,$currentPage);
            $combinedPosts = $combinedPosts->concat($sourceData[$source]);
        }
        $combinedPosts = $combinedPosts->unique('postId')->sortByDesc('createdAt');
        $desiredTotal = $combinedPosts->count();
        $pagedPosts = $combinedPosts->forPage($currentPage, $perPage)->values();

        $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return $list;
    }





}