<?php


namespace App\Helpers;

use App\Http\Controllers\FactCheck\FactCheckController;
use App\Models\AdPost;
use App\Models\AdTarget;
use App\Models\Archive;
use App\Models\FactCheck;
use App\Models\LeaderFollowers;
use App\Models\Likes;
use App\Models\PartyFollowers;
use App\Models\PartyLogin;
use App\Models\PollsByCitizenDetails;
use App\Models\PollsByLeaderDetails;
use App\Models\PollsByPartyDetails;
use App\Models\Post;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use App\Models\PostSeen;
use App\Models\ReportPost;
use App\Models\State;
use App\Models\UserAddress;
use App\Models\UserFollowerTag;
use App\Models\User;
use App\Models\Party;
use App\Models\Ad;
use Auth;
use Crypt;
use Illuminate\Pagination\LengthAwarePaginator;

class FetchAllTrendingPost
{
    public static function getAllPostsFromLeaders($parameterCondition)
    {
        $limit = $parameterCondition['limit'];
        $offset = $parameterCondition['offSet'];
        $currentPage = $parameterCondition['currentPage'];
        $partyId = $parameterCondition['partyId'];
        $keyword = $parameterCondition['keyword'];
        $activity = $parameterCondition['activity'];
        $archieve = $parameterCondition['archieve'];
        $createdBy = $parameterCondition['createdBy'];
        $postByFilter = $parameterCondition['postByFilter'];
        $getTagedPost = $parameterCondition['getTagedPost'];
        $userId = $partyId ?? Auth::user()->id;
        $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        $leaderFollowers = LeaderFollowers::where('followerId', $userId)->pluck('leaderId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userTags = UserAddress::where('userId', $userId)->first();
        $cityTown = $userTags->cityTown ?? null;
        $state = $userTags->state ?? null;
        $district = $userTags->district ?? null;
        $reportedPost = ReportPost::where('reportedBy', $userId)->pluck('postId');

        $combinedPosts = PostByLeader::where(function ($query) use ($leaderFollowers, $seenPostIds, $keyword, $cityTown, $state, $district, $activity, $archieve, $createdBy, $postByFilter, $userId, $getTagedPost, $partyId,  $reportedPost) {
            if ($keyword == '' && $activity == '' && $archieve == '' && $createdBy == '' && $postByFilter == '' && $getTagedPost == false) {
                $query->where('leaderId', $userId)
                    ->where('isPublished', true)
                    ->whereNotIn('post_by_leaders.id', $seenPostIds);

                $query->orWhereIn('leaderId', $leaderFollowers)
                    ->where('isPublished', true)
                    ->whereNotIn('post_by_leaders.id', $seenPostIds);
                $consituencyFollowers = UserFollowerTag::where('userId', $userId)->pluck('followedTags');
                $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
                $userName = Auth::user()->userName;

                $query->orWhere(function ($query) use ($cityTown, $state, $district, $seenPostIds, $fullname, $consituencyFollowers, $userName, $partyId) {
                    $query->where('mention', 'like', "%$cityTown%")
                        ->orWhere('mention', 'like', "%$state%")
                        ->orWhere('mention', 'like', "%$userName%")
                        ->orWhere('mention', 'like', "%$district%")
                        ->orWhere('hashTags', 'like', "%$cityTown")
                        ->orWhere('hashTags', 'like', "%$state")
                        ->orWhere('hashTags', 'like', "%$district")
                        ->orWhere('mention', 'like', "%$consituencyFollowers")
                        ->where('isPublished', true)
                        ->where('isAds', false)
                        ->whereNotIn('post_by_leaders.id', $seenPostIds);

                });

            }
            if ($keyword != '') {
                $query->where('postTitle', 'like', "%$keyword%")
                    ->orWhere('post_by_leaders.mention', 'like', "%$keyword")
                    ->orWhere('post_by_leaders.hashTags', 'like', "%$keyword");
            }

            if (!empty($activity)) {
                $query->whereHas('user', function ($query) use ($activity) {
                    $query->where('leaderId', $activity);
                })->orWhereHas('likes', function ($query) use ($activity) {
                    $query->where('LikeById', $activity);
                })->orWhereHas('comments', function ($query) use ($activity) {
                    $query->where('commentById', $activity);
                });
            }


            if (!empty($archieve)) {
                $query->whereIn('id', $archieve);
            }

            if (!empty($createdBy)) {
                $query->where('leaderId', $createdBy);
            }
            if (!empty($postByFilter)) {
                $query->where('postType', $postByFilter);
            }
            if ($getTagedPost) {
                $userId = $partyId ?? Auth::user()->id;
                $userName = Party::find($userId) ? Party::find($userId)->name : (User::find($userId) ? User::find($userId)->firstName : null);
                $userNameLower = strtolower($userName);

                $query->where(function ($query) use ($userNameLower) {
                    $query->whereNotNull('mention')
                        ->where(function ($query) use ($userNameLower) {
                            $query->whereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%$userNameLower%"])
                                ->orWhereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%@{$userNameLower}%"]);
                        })
                        ->orWhereNotNull('mention')
                        ->where(function ($query) use ($userNameLower) {
                            $query->whereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%$userNameLower%"])
                                ->orWhereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%@{$userNameLower}%"]);
                        });
                });
            }

        })

            ->with([
                'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
                'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
                'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
                'user',
                'leader',
                'user.userDetails',
            ])
            ->orderBy('createdAt', 'desc')
            ->take($limit)
            ->where('isPublished', true)
            ->offset($offset)
            ->whereNotIn('post_by_leaders.id',$reportedPost)
            ->get();

        $formattedLeaderPosts = $combinedPosts->map(function ($post) use ($currentPage, $rolename, $userId, $partyId) {
            $poll = PollsByLeaderDetails::where('postByLeaderId', $post->id)->first();
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';

            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }
            $postByFirstName = $post->user->firstName;
            $postByLastName = $post->user->lastName;
            $postByFullName = $postByFirstName . " " . $postByLastName;
            $likes = new Likes();
            $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
            $isLiked = $hasLiked ? true : false;


            $following = LeaderFollowers::where("followerId", Auth::user()->id)
                ->where("leaderId", $post->leaderId)
                ->exists();
            $formattedDate = $post->createdAt->diffForHumans();
            $imageUrls = [
                optional($post->postByLeaderMetas)->first()->imageUrl1 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl2 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl3 ?? null,
                optional($post->postByLeaderMetas)->first()->imageUrl4 ?? null,
            ];
            $imageUrls = array_filter($imageUrls);

            $optionCounts = $post->pollsByLeaderDetails->pluck('optionCount')->sortByDesc('optionCount')->toArray();

            $totalSum = array_sum($optionCounts);

            if ($totalSum !== 0) {
                $percentages = [];

                $data = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $data[] = ['count' => $count, 'percentage' => $percentage];
                }

                usort($data, function ($a, $b) {
                    return $b['percentage'] - $a['percentage'];
                });

                $optionCounts = array_column($data, 'count');
                $percentages = array_column($data, 'percentage');
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }

            $isEditable = $post->user->id === Auth::user()->id;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Leaders";
            $userId = $partyId ?? Auth::user()->id;
            $isOwnPost = $userId == $post->leaderId;
            $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
            if ($post->anonymous == true) {
                $postByFullName = 'Anonymous';
                $profileImage = null;
            }
            $canVote = false;


            if ($post->postType == "Polls" && $post->postByLeaderMetas->isNotEmpty()) {
                $pollEndDate = $post->postByLeaderMetas->first()->PollendDate;
                $pollEndTime = $post->postByLeaderMetas->first()->pollendTime;

                $currentDateTime = \Carbon\Carbon::now('UTC');

                $pollEndDateTime = \Carbon\Carbon::parse($pollEndDate, 'UTC')
                    ->add(\Carbon\CarbonInterval::milliseconds(113))
                    ->add(\Carbon\CarbonInterval::addMicroseconds(722000));

                if ($currentDateTime->isBefore($pollEndDateTime)) {
                    $canVote = true;
                }
            }
            $designation = optional($post->leader)->leaderElectedRole;
            $leaderParty = (!empty($post->leader)) ? $post->leader->getLeaderCoreParty->party->nameAbbrevation : null;
            $designation = $designation . (!empty($leaderParty) ? "|" . $leaderParty : "");

            return [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => $post->user->userName,
                'postByProfilePicture' => $profileImage,
                'isLiked' => $isLiked,
                'designation' =>  $designation,
                'likedType' => $hasLiked,
                'authorType' => 'Leader',
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
                'pollOption' => ($post->pollsByLeaderDetails ?
                    $post->pollsByLeaderDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray() :
                    []),
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
                'isOwnPost' => $isOwnPost,
                'createdBy' => $post->leaderId,
                'isCreatedByAdmin' => false,
                'isAds' => false,
                'sponserLink' => null,
                'canVote' => $canVote,
                'isPublished' => $post->isPublished

            ];

       }) ->reject(function ($post) {
        return !$post['isPublished']; 
    });


        return $formattedLeaderPosts;
    }



    public static function getAllPostsFromParty($parameterCondition)
    {

        $limit = $parameterCondition['limit'];
        $offset = $parameterCondition['offSet'];
        $currentPage = $parameterCondition['currentPage'];
        $partyId = $parameterCondition['partyId'];
        $keyword = $parameterCondition['keyword'];
        $activity = $parameterCondition['activity'];
        $archieve = $parameterCondition['archieve'];
        $createdBy = $parameterCondition['createdBy'];
        $postByFilter = $parameterCondition['postByFilter'];
        $reportedPost = ReportPost::where('reportedBy', $partyId)->pluck('postId');

        $userId = $parameterCondition['partyId'] ?? Auth::user()->id;
        $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];

        $partyFollowers = PartyFollowers::where('followerId', $userId)->pluck('partyId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userPartyIds = PartyLogin::where('userId', $userId)->pluck('partyId');

        if ($keyword == '' && $activity == '' && $archieve == '') {
            $partyPosts = PostByParty::whereIn('partyId', $partyFollowers)
                ->orWhere(function ($query) use ($userPartyIds) {
                    $query->whereIn('partyId', $userPartyIds);
                });
        } else {
            $partyPosts = PostByParty::select('*');
        }
        $userTags = UserAddress::where('userId', $userId)->first();
        $cityTown = $userTags->cityTown ?? null;
        $state = $userTags->state ?? null;
        $district = $userTags->district ?? null;
        $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
        $userName = Auth::user()->userName;
        $partyPosts = $partyPosts->orWhere(function ($query) use ($cityTown, $state, $district, $userName, $keyword, $seenPostIds, $activity, $postByFilter, $partyId, $archieve, $createdBy, $rolename,$reportedPost) {
            if (empty($keyword) && empty($activity) && empty($archieve) && empty($createdBy) && empty($postByFilter)) {
                $query->orWhere(function ($query) use ($cityTown, $state, $district, $userName, $seenPostIds,$reportedPost) {
                    $query->where('post_by_parties.mention', 'like', "%$cityTown%")
                        ->orWhere('post_by_parties.mention', 'like', "%$userName%")
                        ->orWhere('post_by_parties.mention', 'like', "%$state%")
                        ->orWhere('post_by_parties.mention', 'like', "%$district%")
                        ->orWhere('post_by_parties.hashTags', 'like', "%$cityTown")
                        ->orWhere('post_by_parties.hashTags', 'like', "%$state")
                        ->orWhere('post_by_parties.hashTags', 'like', "%$district")
                        ->whereNotIn('post_by_parties.id', $seenPostIds)
                        ->where('isAds', false)
                        ->whereNotIn('post_by_parties.id', Archive::pluck('postId')->toArray());


                });
            } elseif (!empty($keyword)) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('postTitle', 'like', "%$keyword%")
                        ->orWhere('post_by_parties.mention', 'like', "%$keyword")
                        ->orWhere('post_by_parties.hashTags', 'like', "%$keyword");
                });
            }

            if (!empty($activity)) {
                $query->where(function ($query) use ($activity) {
                    $query->whereHas('party', function ($query) use ($activity) {
                        $query->where('partyId', $activity);
                    })->orWhereHas('likes', function ($query) use ($activity) {
                        $query->where('LikeById', $activity);
                    })->orWhereHas('comments', function ($query) use ($activity) {
                        $query->where('commentById', $activity);
                    });
                });
            }

            if (!empty($archieve)) {
                $query->whereIn('post_by_parties.id', $archieve);
            }

            if (!empty($createdBy)) {
                $query->where(function ($query) use ($createdBy) {
                    $query->where('post_by_parties.partyId', $createdBy);
                });
            }

            if (!empty($postByFilter)) {
                $query->where('postType', $postByFilter);
            }

            $query->where('isPublished', true);
        });

        $partyPosts = $partyPosts->join('parties', 'parties.id', '=', 'post_by_parties.partyId')
            ->with([
                'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
                'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
                'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
            ])
            ->orderBy('post_by_parties.createdAt', 'desc')
            ->select('post_by_parties.*', 'parties.name', 'parties.logo')
            ->where('isPublished',TRUE)
            ->whereNotIn('post_by_parties.id',$reportedPost)

            // ->whereNotIn('post_by_parties.id', Archive::pluck('postId')->toArray())
        ->take($limit)
            ->offset($offset)
            ->get();

        $formattedPartyPosts = $partyPosts->map(function ($post) use ($userId, $partyId, $currentPage, $rolename) {
            $userVote = null;
          
            $isUserVoted = false;
            $selectedOption = '';
            
            $poll = PollsByPartyDetails::where('postByPartyId', $post->id)->first();
            $following = PartyFollowers::where("followerId", Auth::user()->id)
                ->where("partyId", $post->partyId)
                ->exists();
            
            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
            
                if ($userVote !== null) {
                    $isUserVoted = true;
                    $selectedOption = $userVote;
                }
            }
            
            $likes = new Likes();
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

                $data = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $data[] = ['count' => $count, 'percentage' => $percentage];
                }

                usort($data, function ($a, $b) {
                    return $b['percentage'] - $a['percentage'];
                });

                $optionCounts = array_column($data, 'count');
                $percentages = array_column($data, 'percentage');
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }

            $isEditable = ($partyId !== '') ? ($post->partyId === $partyId) : false;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Party";
            $postByName = $post->name;
            $profileImage = $post->logo;
            if ($post->anonymous === true) {
                $postByName = 'Anonymous';
                $profileImage = null;
            }
            $canVote = false;
            if ($post->postType == "Polls" && $post->postByPartyMetas->isNotEmpty()) {
                $pollEndDate = $post->postByPartyMetas->first()->PollendDate;
                $pollEndTime = $post->postByPartyMetas->first()->pollendTime;

                $currentDateTime = \Carbon\Carbon::now('UTC');

                $pollEndDateTime = \Carbon\Carbon::parse($pollEndDate, 'UTC')
                    ->add(\Carbon\CarbonInterval::milliseconds(113))
                    ->add(\Carbon\CarbonInterval::addMicroseconds(722000));

                if ($currentDateTime->isBefore($pollEndDateTime)) {
                    $canVote = true;
                }
            }
            $isOwnPost = $partyId === $post->partyId;
            return [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByName,
                'postByUserName' => null,
                'postByProfilePicture' => $profileImage,
                'isLiked' => $isLiked,
                'likedType' => $hasLiked,
                'authorType' => 'Party',
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
                'pollOption' => ($post->pollsByPartyDetails ?
                    $post->pollsByPartyDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray() :
                    []),
                'optionCount' => $percentages,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => optional($post->postByPartyMetas)->first()?->PollendDate,
                'pollendTime' => optional($post->postByPartyMetas)->first()?->pollendTime,
                'complaintLocation' => optional($post->postByPartyMetas)->first()?->complaintLocation,
                'optionLength' => count($post->pollsByPartyDetails),
                'eventsLocation' => optional($post->eventsByParty)->first()?->eventsLocation,
                'eventStartDate' => optional($post->eventsByParty)->first()?->startDate,
                'eventsEndDate' => optional($post->eventsByParty)->first()?->endDate,
                'eventStartTime' => optional($post->eventsByParty)->first()?->startTime,
                'eventsEndTime' => optional($post->eventsByParty)->first()?->endTime,
                'IsFollowing' => $following,
                'postCreatedAt' => $formattedDate,
                'IsEditable' => $isEditable,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' => $isOwnPost,
                'createdBy' => $post->partyId,
                'isCreatedByAdmin' => false,
                'isAds' => false,
                'sponserLink' => null,
                'canVote' => $canVote,
                'isPublished' => $post->isPublished


            ];
        }) ->reject(function ($post) {
        return !$post['isPublished']; 
    });

        return $formattedPartyPosts;
    }


    public static function getAllPostsFromCitizens($parameterCondition)
    {
        $limit = $parameterCondition['limit'];
        $offset = $parameterCondition['offSet'];
        $currentPage = $parameterCondition['currentPage'];
        $keyword = $parameterCondition['keyword'];
        $activity = $parameterCondition['activity'];
        $archieve = $parameterCondition['archieve'];
        $createdBy = $parameterCondition['createdBy'];
        $partyId = $parameterCondition['partyId'];
        $postByFilter = $parameterCondition['postByFilter'];
        $getTagedPost = $parameterCondition['getTagedPost'];

        $userId = $parameterCondition['partyId'] ?? Auth::user()->id;
        $userTags = UserAddress::where('userId', $userId)->first();
        $cityTown = $userTags->cityTown ?? null;
        $state = $userTags->state ?? null;
        $district = $userTags->district ?? null;
        $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        $userName = Auth::user()->userName;
        $reportedPost = ReportPost::where('reportedBy', $userId)->pluck('postId');

        $citizensPosts = PostByCitizen::where(function ($query) use ($cityTown, $state, $district, $userName, $keyword, $activity, $archieve, $createdBy, $userId, $postByFilter, $getTagedPost, $partyId,$reportedPost) {
            if ($keyword == '' && $activity == '' && $archieve == '' && $createdBy == '' && $getTagedPost == false) {

                $query->orWhere('mention', 'like', "%$cityTown%")
                    ->orWhere('mention', 'like', "%$state%")
                    ->orWhere('mention', 'like', "%$userName%")
                    ->orWhere('mention', 'like', "%$district%")
                    ->orWhere('hashTags', 'like', "%$cityTown")
                    ->orWhere('hashTags', 'like', "%$state")
                    ->orWhere('hashTags', 'like', "%$district")
                    ->orWhere('citizenId', '=', Auth::user()->id)
                    ->where('isPublished', true)
                    ->where('isAds', false);

            }
            if ($keyword != '') {
                $query->where('postTitle', 'ILIKE', "%$keyword%")
                    ->orWhere('mention', 'like', "%$keyword")
                    ->orWhere('hashTags', 'like', "%$keyword");
            }
            if (!empty($activity)) {
                $query->whereHas('user', function ($query) use ($activity) {
                    $query->where('citizenId', $activity);
                })->orWhereHas('likes', function ($query) use ($activity) {
                    $query->where('LikeById', $activity);
                })->orWhereHas('comments', function ($query) use ($activity) {
                    $query->where('commentById', $activity);
                });
            }
            if (!empty($archieve)) {
                $query->whereIn('id', $archieve);
            }
            if (!empty($createdBy)) {
                $query->where('citizenId', $createdBy);
            }
            if (!empty($postByFilter)) {
                $query->where('postType', $postByFilter);
            }
            if ($getTagedPost) {
                $userId = $partyId ?? Auth::user()->id;
                $userName = Party::find($userId) ? Party::find($userId)->name : (User::find($userId) ? User::find($userId)->firstName : null);
                $userNameLower = strtolower($userName);
                $userNameWithoutAt = str_replace('@', '', $userNameLower);

                $query->where(function ($query) use ($userNameLower, $userNameWithoutAt) {
                    $query->whereNotNull('mention')
                        ->where(function ($query) use ($userNameLower, $userNameWithoutAt) {
                            $query->whereRaw("LOWER(mention) LIKE LOWER(?)", ["%$userNameLower%"])
                                ->orWhereRaw("LOWER(mention) LIKE LOWER(?)", ["%$userNameWithoutAt%"]);
                        });
                });
            }
             

        })
            ->with([
                'postByCitizenMetas:id,postByCitizenId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByCitizenDetails:id,postByCitizenId,pollOption,optionCount',
                'pollsByCitizenDetails.pollsByCitizenVotes:id,pollsByCitizenDetailsId',
                'user',
                'user.userDetails'
            ])
            ->whereNotIn('post_by_citizens.id', $seenPostIds)
            // ->whereNotIn('post_by_citizens.id', Archive::pluck('postId')->toArray())
            ->orderBy('createdAt', 'desc')
            // ->where('isPublished', true)
            ->take(10)
            ->offset($offset)
            ->whereNotIn('post_by_citizens.id',$reportedPost)
            ->get();
        $formattedCitizenPosts = $citizensPosts->map(function ($post) use ($currentPage, $userId, $rolename) {
            $poll = PollsByCitizenDetails::where('postByCitizenId', $post->id)->first();
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';

            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }
            $postByFirstName = $post->user->firstName;
            $postByLastName = $post->user->lastName;
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

                $data = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $data[] = ['count' => $count, 'percentage' => $percentage];
                }

                usort($data, function ($a, $b) {
                    return $b['percentage'] - $a['percentage'];
                });

                $optionCounts = array_column($data, 'count');
                $percentages = array_column($data, 'percentage');
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }


            $isEditable = $post->user->id === Auth::user()->id;
            $complaintStatus = null;
            if ($post->postType == 'Complaint') {
                $complaintStatus = false;
            }
            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);

            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Citizen";
            $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
            if ($post->anonymous === true) {
                $postByFullName = 'Anonymous';
                $profileImage = null;
            }
            $canVote = false;
            if ($post->postType == "Polls" && $post->postByCitizenMetas->isNotEmpty()) {
                $pollEndDate = $post->postByCitizenMetas->first()->PollendDate;
                $pollEndTime = $post->postByCitizenMetas->first()->pollendTime;

                $currentDateTime = \Carbon\Carbon::now('UTC');

                $pollEndDateTime = \Carbon\Carbon::parse($pollEndDate, 'UTC')
                    ->add(\Carbon\CarbonInterval::milliseconds(113))
                    ->add(\Carbon\CarbonInterval::addMicroseconds(722000));

                if ($currentDateTime->isBefore($pollEndDateTime)) {
                    $canVote = true;
                }
            }
            $isOwnPost = Auth::user()->id == $post->citizenId;
            return [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => $post->user->userName,
                'postByProfilePicture' => $profileImage,
                'isLiked' => $isLiked,
                'likedType' => $hasLiked,
                'authorType' => 'Citizen',
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
                'pollOption' => ($post->pollsByCitizenDetails ?
                    $post->pollsByCitizenDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray() :
                    []),
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
                'isOwnPost' => $isOwnPost,
                'createdBy' => $post->citizenId,
                'isCreatedByAdmin' => false,
                'isAds' => false,
                'sponserLink' => null,
                'canVote' => $canVote,
                'isPublished' => $post->isPublished

            ];
        })->reject(function ($post) {
        return !$post['isPublished']; 
    });

        return $formattedCitizenPosts;
    }

    public static function getAllFromFactCheck($parameterCondition)
    {
        $limit = $parameterCondition['limit'];
        $offset = $parameterCondition['offSet'];
        $currentPage = $parameterCondition['currentPage'];
        $partyId = $parameterCondition['partyId'];
        $userId = $partyId ?? Auth::user()->id;
        $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];
        $factPost = FactCheck::pluck('postId');
        $combinedPosts = PostByLeader::where(function ($query) use ($factPost) {

            if (!empty($factPost)) {
                $query->whereIn('id', $factPost);
            }
        })
            ->with([
                'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
                'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
                'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
                'user',
                'user.userDetails',
            ])
            ->orderBy('createdAt', 'desc')
            ->take($limit)
            ->offset($offset)
            ->get();

        $formattedLeaderPosts = $combinedPosts->map(function ($post) use ($currentPage, $rolename, $userId) {
            $poll = $post->pollsByLeaderDetails->first();
            $userVote = null;
            $isUserVoted = false;
            $selectedOption = '';

            if ($poll) {
                $userVote = $poll->userVote($userId, $post->id);
                $isUserVoted = $userVote !== null;
                $selectedOption = $isUserVoted ? $userVote : '';
            }
            $postByFirstName = $post->user->firstName;
            $postByLastName = $post->user->lastName;
            $postByFullName = $postByFirstName . " " . $postByLastName;
            $likes = new Likes();
            $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
            $isLiked = $hasLiked ? true : false;

            $following = LeaderFollowers::where("followerId", Auth::user()->id)
                ->where("leaderId", $post->leaderId)
                ->exists();
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

                $data = [];
                foreach ($optionCounts as $count) {
                    $percentage = ceil(($count / $totalSum) * 100);
                    $data[] = ['count' => $count, 'percentage' => $percentage];
                }

                usort($data, function ($a, $b) {
                    return $b['percentage'] - $a['percentage'];
                });

                $optionCounts = array_column($data, 'count');
                $percentages = array_column($data, 'percentage');
            } else {
                $percentages = array_fill(0, count($optionCounts), 0);
            }

            $isEditable = $post->user->id === Auth::user()->id;
            $complaintStatus = $post->postType == 'Complaint' ? false : null;
            $url = env('APP_URL');
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Leaders";
            $isOwnPost = User::where('id', $post->leaderId)->exists();
            $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
            if ($post->anonymous == true) {
                $postByFullName = 'Anonymous';
                $profileImage = null;
            }

            return [
                'postURL' => $postURL,
                'postId' => $post->id,
                'postByName' => $postByFullName,
                'postByUserName' => $post->user->userName,
                'postByProfilePicture' => $profileImage,
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
                'ideaDepartment' => optional($post->postByLeaderMetas)->pluck('ideaDepartment')->first(),
                'postDescriptions' => optional($post->postByLeaderMetas)->pluck('postDescriptions')->first(),
                'image' => $imageUrls,
                'pollOption' => ($post->pollsByLeaderDetails ?
                    $post->pollsByLeaderDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray() :
                    []),
                'optionCount' => $percentages,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'pollendDate' => optional($post->postByLeaderMetas)->pluck('PollendDate')->first(),
                'pollendTime' => optional($post->postByLeaderMetas)->pluck('pollendTime')->first(),
                'complaintLocation' => optional($post->postByLeaderMetas)->pluck('complaintLocation')->first(),
                'optionLength' => count($post->pollsByLeaderDetails),
                'eventsLocation' => optional($post->eventsByLeader)->pluck('eventsLocation')->first(),
                'eventStartDate' => optional($post->eventsByLeader)->pluck('startDate')->first(),
                'eventsEndDate' => optional($post->eventsByLeader)->pluck('endDate')->first(),
                'eventStartTime' => optional($post->eventsByLeader)->pluck('startTime')->first(),
                'eventsEndTime' => optional($post->eventsByLeader)->pluck('endTime')->first(),
                'IsFollowing' => $following,
                'IsEditable' => $isEditable,
                'postCreatedAt' => $formattedDate,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' => $isOwnPost,
                'createdBy' => $post->leaderId,
                'isCreatedByAdmin' => true,
                'isAds' => false,
                'sponserLink' => null,
                'canVote' => false


            ];
        });

        return $formattedLeaderPosts;


    }

    public static function getAds($parameterCondition)
    {
        $userId = Auth::user()->id;
        $userAddress = UserAddress::where('userId', $userId)->first();
        $limit = $parameterCondition['limit'];
        $offset = $parameterCondition['offSet'];
        $currentPage = $parameterCondition['currentPage'];
        $partyId = $parameterCondition['partyId'];
        $keyword = $parameterCondition['keyword'];
        $activity = $parameterCondition['activity'];
        $archieve = $parameterCondition['archieve'];
        $createdBy = $parameterCondition['createdBy'];
        $postByFilter = $parameterCondition['postByFilter'];
        $getTagedPost = $parameterCondition['getTagedPost'];
        
        if( $keyword == '' && $activity == '' &&  $archieve == '' &&  $createdBy=='' &&    $postByFilter=='' && $getTagedPost==false)
        {
            if ($userAddress) {
                $stateId = State::where('name', $userAddress->state)->value('id');
    
                $adsIds = AdTarget::where('stateId', $stateId)->pluck('adId')->toArray();
     
                $getRunnableAdds = Ad::whereIn('id', $adsIds)->where('status', 'Active')->pluck('id')->toArray();
                 
                $adPost = AdPost::whereIn('adsId', $getRunnableAdds)->pluck('postId')->toArray();
    
                $adPostLeader = PostByLeader::whereIn('id', $adPost)->where('isAds', true)
                    ->with([
                        'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                        'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
                        'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
                        'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
                        'user',
                        'user.userDetails',
                    ])
                    ->take($limit)
                    ->offset($offset)
                    ->get();
                $adPostParty = PostByParty::whereIn('id', $adPost)->where('isAds', true)->with([
                    'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
                    'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
                    'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
                    'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
                    'party'
                ])
                    ->take($limit)
                    ->offset($offset)
                    ->get();
    
                $combinedAdPosts = $adPostLeader->concat($adPostParty);
                $rolename = $partyId !== null ? 'Party' : Auth::user()->getRoleNames()[0];

                $formattedLeaderPosts = $combinedAdPosts->map(function ($post) use ($currentPage, $rolename, $userId) {
                    $pollDetailsKey = $post instanceof PostByLeader ? 'pollsByLeaderDetails' : 'pollsByPartyDetails';
                    $metasKey = $post instanceof PostByLeader ? 'postByLeaderMetas' : 'postByPartyMetas';
    
                    $poll = $post->$pollDetailsKey()->first();
                    $userVote = null;
                    $isUserVoted = false;
                    $selectedOption = '';
    
                    if ($poll) {
                        $userVote = $poll->userVote($userId, $post->id);
                        $isUserVoted = $userVote !== null;
                        $selectedOption = $isUserVoted ? $userVote : '';
                    }
    
                    $postByFirstName = !empty($post->user) ? $post->user->firstName : 'DefaultFirstName';
                    $postByLastName = !empty($post->user) ? $post->user->lastName : 'DefaultLastName';
                    $postByFullName = $postByFirstName . " " . $postByLastName;
    
                    // if ($post instanceof PostByParty && !empty($post)) {
                    //     $postByFullName = $post->party->name;
                    // }
                    $likes = new Likes();
                    $hasLiked = $likes->hasLiked($rolename, $userId, $post->id, $post->authorType);
                    $isLiked = $hasLiked ? true : false;
    
                    $following = LeaderFollowers::where("followerId", Auth::user()->id)
                        ->where("leaderId", $post->leaderId)
                        ->exists();
                    $formattedDate = $post->createdAt->diffForHumans();
                    $imageUrls = [
                        optional($post->$metasKey)->first()->imageUrl1 ?? null,
                        optional($post->$metasKey)->first()->imageUrl2 ?? null,
                        optional($post->$metasKey)->first()->imageUrl3 ?? null,
                        optional($post->$metasKey)->first()->imageUrl4 ?? null,
                    ];
                    $imageUrls = array_filter($imageUrls);
    
                    $optionCounts = $post->$pollDetailsKey->pluck('optionCount')->sortByDesc('optionCount')->toArray();
    
                    $totalSum = array_sum($optionCounts);
    
                    if ($totalSum !== 0) {
                        $percentages = [];
    
                        $data = [];
                        foreach ($optionCounts as $count) {
                            $percentage = ceil(($count / $totalSum) * 100);
                            $data[] = ['count' => $count, 'percentage' => $percentage];
                        }
    
                        usort($data, function ($a, $b) {
                            return $b['percentage'] - $a['percentage'];
                        });
    
                        $optionCounts = array_column($data, 'count');
                        $percentages = array_column($data, 'percentage');
                    } else {
                        $percentages = array_fill(0, count($optionCounts), 0);
                    }
    
                    $isEditable = !empty($post->user) ? ($post->user->id === Auth::user()->id) : false;
                    $complaintStatus = null;
                    if ($post->postType == 'Complaint') {
                        $complaintStatus = false;
                    }
                    $url = env('APP_URL');
                    $encryptedPostId = EncryptionHelper::encryptString($post->id);
                    $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=Leaders";
                    $isOwnPost = Auth::user()->id = $post->leaderId;
                    $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
                    if ($post->anonymous == true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    $adsId = AdPost::where('postId', $post->id)->first();
                    $ads = Ad::find($adsId->adsId);
                    $sponserLink = $ads->url;
    
                    return [
                        'postURL' => $postURL,
                        'postId' => $post->id,
                        'postByName' => $postByFullName,
                        'postByUserName' => !empty($post->user) ? $post->user->userName : 'Anonymous',
                        'postByProfilePicture' => $profileImage,
                        'isLiked' => $isLiked,
                        'likedType' => $hasLiked,
                        'authorType' => 'Leader',
                        'leaderId' => $post->leaderId,
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
                        'optionCount' => (!empty($post->pollsByLeaderDetails)) ? $percentages : null,
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
                        'IsFollowing' => $following,
                        'IsEditable' => $isEditable,
                        'postCreatedAt' => $formattedDate,
                        'createdAt' => $post->createdAt,
                        'currentPage' => $currentPage,
                        'complaintStatus' => $complaintStatus,
                        'isOwnPost' => false,
                        'createdBy' => $post->leaderId,
                        'isCreatedByAdmin' => false,
                        'isAds' => true,
                        'sponserLink' => $sponserLink,
                        'canVote' => false,
                        'isPublished' => $post->isPublished
                    ];
                })->reject(function ($post) {
        return !$post['isPublished']; 
    });
    
                return $formattedLeaderPosts;
            }
        }
        return [];
        

        
    }

    public static function getAllPost($currentPage, $partyId, $keyword, $activity, $archieve, $createdBy, $postByFilter)
    {
        $sources = [
            // 'getAds',
            // 'getAllFromFactCheck',
            'getAllPostsFromParty',
            'getAllPostsFromCitizens',
            'getAllPostsFromLeaders',
        ];
        $getTagedPost = false;

        
        $currentDateTime = date("Y-m-d\TH:i:s\Z");


        if ($postByFilter != '') {
            if (
                (!empty($createdBy) && $postByFilter !== '' && $postByFilter !== 'Events' && $postByFilter !== 'Polls') ||
                ($postByFilter === 'Idea' || $postByFilter === 'Complaint')
            ) {
                $decryptedData = Crypt::decrypt($createdBy);
                [$createdBy, $createdByType] = $decryptedData;
                if ($createdByType == 'Leader' || $createdByType == 'Party') {
                    $getTagedPost = true;
                }
                switch ($createdByType) {
                    case "Leader":
                        $sources = ['getAllPostsFromLeaders', 'getAllPostsFromCitizens'];
                        $archieve = [];
                        $createdBy = '';
                        break;
                    case "Party":
                        $sources = ['getAllPostsFromLeaders', 'getAllPostsFromCitizens'];
                        $createdBy = '';
                        break;
                    case "Citizen":
                        $sources = ['getAllPostsFromCitizens'];
                        break;
                    default:
                        break;
                }
            }
        }
        if (!empty($createdBy) && ($postByFilter == '' || $postByFilter == 'Events' || $postByFilter == 'Polls')) {
            $decryptedData = Crypt::decrypt($createdBy);
            [$createdBy, $createdByType] = $decryptedData;
            switch ($createdByType) {
                case "Leader":
                    $sources = ['getAllPostsFromLeaders'];
                    break;
                case "Party":
                    $sources = ['getAllPostsFromParty'];
                    break;
                case "Citizen":
                    $sources = ['getAllPostsFromCitizens'];
                    break;
                default:
                    break;
            }
        }

        $combinedPosts = collect([]);
        $perPage = 5;
        $sourceData = [];
        $limit = env('MAX_POSTS_PER_SOURCE', 500);
        $parameterCondition = [
            "limit" => $limit,
            "offSet" => 0,
            "partyId" => $partyId,
            "currentPage" => $currentPage,
            "keyword" => $keyword,
            "activity" => $activity,
            "archieve" => $archieve,
            "createdBy" => $createdBy,
            "postByFilter" => $postByFilter,
            'getTagedPost' => $getTagedPost

        ];
        
        // return self::getAllPostsFromCitizens($parameterCondition);
        foreach ($sources as $source) {
            $sourceData[$source] = self::$source($parameterCondition);
            $combinedPosts = $combinedPosts->concat($sourceData[$source]);
        }
        $combinedPosts = $combinedPosts->unique('postId')->sortByDesc('createdAt');
         
        if (empty($archieve)) {
            $postsToRemove = Archive::pluck('postId')->toArray();
            $combinedPosts = $combinedPosts->reject(function ($post) use ($postsToRemove) {
                return in_array($post['postId'], $postsToRemove);
            });
       
        if (!empty($createdBy) && $createdByType == 'Party') {
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy) {
                return isset($post['partyId']) && $post['partyId'] == $createdBy;
            });
        }

        
       
        if ($postByFilter == 'Events') {
            $currentDateTime = date("Y-m-d\TH:i:s\Z");
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy,  $currentDateTime ) {
                if ($post['postType'] == 'Events') {
                    $eventsEndDateTime = \Carbon\Carbon::parse("{$post['eventsEndDate']} {$post['eventsEndTime']}", 'Asia/Kolkata');
                    return $eventsEndDateTime->isPast();
                }
            });
        }
        if ($postByFilter == '') {
            $currentDateTime = date("Y-m-d\TH:i:s\Z");
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy,$currentDateTime) {
                if ($post['postType'] == 'Events') {
                    $eventsEndDateTime = \Carbon\Carbon::parse("{$post['eventsEndDate']} {$post['eventsEndTime']}", 'Asia/Kolkata');
                    return $eventsEndDateTime->isFuture();
                } elseif ($post['postType'] == 'Polls') {
                    $pollEndDateTime = \Carbon\Carbon::parse("{$post['pollendDate']} {$post['pollendTime']}", 'Asia/Kolkata');
                    return $pollEndDateTime->isFuture();
                }

                return true;
            });
        }
    }

    if ($postByFilter == 'Polls') {
        $currentDateTime = date("Y-m-d\TH:i:s\Z");
        $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy,   $currentDateTime) {
            if ($post['postType'] == 'Polls') {
                $pollEndDateTime = \Carbon\Carbon::parse("{$post['pollendDate']} {$post['pollendTime']}", 'Asia/Kolkata');
                return $pollEndDateTime->isPast();
            }
        });
    }
        $desiredTotal = $combinedPosts->count();
        $pagedPosts = $combinedPosts->forPage($currentPage, $perPage)->values();
        $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return $list;
    }

}