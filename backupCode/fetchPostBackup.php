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
use App\Models\UserDetails;
use App\Models\UserFollowerTag;
use App\Models\User;
use App\Models\Party;
use App\Models\Ad;
use App\Models\Ministry;
use Auth;
use Crypt;
use Illuminate\Pagination\LengthAwarePaginator;
use Request;
use Carbon\Carbon;
use DB;

class OptimizeFetchPost
{
    public static function getAllPost($currentPage, $partysId, $keyword, $activity, $archieve, $createdBy, $postByFilter)
    {
        // return $createdBy;
        $userId = $partysId ?? Auth::user()->id;
        $leaderFollowers = LeaderFollowers::where('followerId', $userId)->pluck('leaderId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userTags = UserAddress::where('userId', $userId)->first();
        $cityTown = $userTags->cityTown ?? null;
        $state = $userTags->state ?? null;
        $district = $userTags->district ?? null;
        $limit = env('MAX_POSTS_PER_SOURCE', 500);
        $featureAdsPost = self::getAds($currentPage, $partysId, $keyword, $activity, $archieve, $createdBy, $postByFilter);
        // return  $featureAdsPost;
        $perPage = 5;
        $partyFollowers = PartyFollowers::where('followerId', $userId)->pluck('partyId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userPartyIds = PartyLogin::where('userId', $userId)->pluck('partyId');
        $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
        $userName = Auth::user()->userName;
        $getTagedPost = false;
        $consituencyFollowers = UserFollowerTag::where('userId', $userId)->pluck('followedTags');
        $loggedInUserId = Auth::user()->id;

        $reportedPost = ReportPost::where('reportedBy', $userId)->pluck('postId');

        // $userAddress = UserAddress::where('userId', Auth::user()->id)->first();

        // $stateId = State::where('name', $userAddress->state)->value('id');

        // $adsIds = AdTarget::where('stateId', $stateId)->pluck('adId')->toArray();

        // $getRunnableAdds = Ad::whereIn('id', $adsIds)->where('status', 'Active')->pluck('id')->toArray();

        // $adPost = AdPost::whereIn('adsId', $getRunnableAdds)->pluck('postId')->toArray();

        $postsByLeader = PostByLeader::with([
            'likes' => function ($query) use ($userId) {
                $query->where('LikeById', $userId);
            },
            'comments',
            'pollsByLeaderVote',
            'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
            'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
            'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
            'user',
            'leader',
            'user.userDetails',
        ])
            ->where('isAds', false)
            ->where(function ($query) use ($leaderFollowers, $loggedInUserId) {
                $query->whereIn('leaderId', $leaderFollowers)
                    ->orWhere('leaderId', $loggedInUserId);
            })
            ->whereNotIn('post_by_leaders.id', $reportedPost)
            ->orderBy('post_by_leaders.createdAt', 'desc');


        $postsByLeader->orWhere(function ($query) use ($cityTown, $state, $district, $seenPostIds, $fullname, $consituencyFollowers, $userName, $partysId, $keyword, $activity, $archieve, $createdBy, $postByFilter, $getTagedPost, $userId, $leaderFollowers) {

            if ($keyword == '' && $activity == '' && $archieve == '' && $createdBy == '' && $postByFilter == '' && $getTagedPost) {



                $query->where('leaderId', $userId)
                    ->where('isPublished', true)
                    ->whereNotIn('post_by_leaders.id', $seenPostIds);

                $query->orWhereIn('leaderId', $leaderFollowers)
                    ->where('isPublished', true)
                    ->whereNotIn('post_by_leaders.id', $seenPostIds);
                $consituencyFollowers = UserFollowerTag::where('userId', $userId)->pluck('followedTags');
                $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
                $userName = Auth::user()->userName;

                $query->orWhere(function ($query) use ($cityTown, $state, $district, $seenPostIds, $fullname, $consituencyFollowers, $userName, $partysId) {
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

        });

        if ($keyword != '') {
            $postsByLeader->where('postTitle', 'like', "%$keyword%")
                ->orWhere('post_by_leaders.mention', 'like', "%$keyword")
                ->orWhere('post_by_leaders.hashTags', 'like', "%$keyword");
        }
        if (!empty($activity)) {
            $activityParts = explode('|', $activity);
            $leaderActivity = $activityParts[0];
            $postType = $activityParts[1];
            $postsByLeader->whereHas('user', function ($query) use ($leaderActivity, $postType) {
                $query->where('leaderId', $leaderActivity)->where('postType', $postType);
            })->orWhereHas('likes', function ($query) use ($leaderActivity, $postType) {
                $query->where('LikeById', $leaderActivity)->where('postType', $postType);
            })->orWhereHas('comments', function ($query) use ($leaderActivity, $postType) {
                $query->where('commentById', $leaderActivity)->where('postType', $postType);
            });
        }

        if (!empty($archieve)) {
            $postsByLeader->whereIn('id', $archieve);
        }

        if (!empty($createdBy)) {
            $postsByLeader->where('leaderId', $createdBy);
        }
        if (!empty($postByFilter)) {
            $postsByLeader->where('postType', $postByFilter);
        }

        // if ($getTagedPost) {
        //     $userId = $partysId ?? Auth::user()->id;
        //     $userName = Party::find($userId) ? Party::find($userId)->name : (User::find($userId) ? User::find($userId)->firstName : null);
        //     $userNameLower = strtolower($userName);

        //     $postsByLeader->where(function ($query) use ($userNameLower) {
        //         $query->whereNotNull('mention')
        //             ->where(function ($query) use ($userNameLower) {
        //                 $query->whereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%$userNameLower%"])
        //                     ->orWhereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%@{$userNameLower}%"]);
        //             })
        //             ->orWhereNotNull('mention')
        //             ->where(function ($query) use ($userNameLower) {
        //                 $query->whereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%$userNameLower%"])
        //                     ->orWhereRaw("LOWER(mention) LIKE LOWER(?) ESCAPE '|'", ["%@{$userNameLower}%"]);
        //             });
        //     });
        // }
        // auth()->user()->parentPart

        $postsByparty = PostByParty::with([
            'party',
            'likes' => function ($query) use ($userId) {
                $query->where('LikeById', $userId);
            },
            'comments',
            'pollsByPartyVote',
            'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
            'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
            'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
        ])
            ->where('isAds', false)
            ->whereNotIn('post_by_parties.id', $reportedPost)
            ->orderBy('post_by_parties.createdAt', 'desc');

        if ($partysId != null) {
            $getParty = Party::where('id', $partysId)->first();
            $getPatentId = ($getParty && $getParty->parentPartyId) ? $getParty->parentPartyId : null;
            if ($getPatentId) {
                $postsByparty->orWhere('post_by_parties.partyId', $getPatentId);
            }
        }

        if (empty($keyword) && empty($activity) && empty($archieve) && empty($createdBy) && empty($postByFilter) && $getTagedPost == false) {

            $postsByparty = $postsByparty->orWhere(function ($query) use ($cityTown, $state, $district, $userName, $seenPostIds, $userPartyIds, $partyFollowers) {
                $query->where(function ($query) use ($cityTown, $userName, $state, $district, $seenPostIds, $userPartyIds, $partyFollowers) {
                    $query->where('post_by_parties.mention', 'like', "%$cityTown%")
                        // ->orWhere('post_by_parties.mention', 'like', "%$userName%")
                        // ->orWhere('post_by_parties.mention', 'like', "%$state%")
                        // ->orWhere('post_by_parties.mention', 'like', "%$district%")
                        // ->orWhere('post_by_parties.hashTags', 'like', "%$cityTown")
                        // ->orWhere('post_by_parties.hashTags', 'like', "%$state")
                        // ->orWhere('post_by_parties.hashTags', 'like', "%$district")
                        // ->whereNotIn('post_by_parties.id', $seenPostIds)
                        ->where('isPublished', true)
                        ->orWhereIn('partyId', $userPartyIds)
                        ->orWhereIn('partyId', $partyFollowers);
                });


            });

            $archivePostIds = Archive::pluck('postId')->filter()->toArray();
            if (count($archivePostIds) > 0) {
                $postsByparty->whereNotIn('post_by_parties.id', $archivePostIds);
            }


        }

        if (!empty($activity)) {
            $activityParts = explode('|', $activity);
            $partyactivity = $activityParts[0];
            $postType = $activityParts[1];

            $postsByparty->where(function ($query) use ($partyactivity, $postType) {
                $query->whereHas('party', function ($query) use ($partyactivity, $postType) {
                    $query->where('partyId', $partyactivity)->where('postType', $postType);
                })->orWhereHas('likes', function ($query) use ($partyactivity, $postType) {
                    $query->where('LikeById', $partyactivity)->where('postType', $postType);
                })->orWhereHas('comments', function ($query) use ($partyactivity, $postType) {
                    $query->where('commentById', $partyactivity)->where('postType', $postType);
                });
            });
        }

        if (!empty($archieve)) {
            $postsByparty->whereIn('post_by_parties.id', $archieve);
        }

        if (!empty($createdBy)) {
            $postsByparty->where(function ($query) use ($createdBy) {
                $query->where('post_by_parties.partyId', $createdBy);
            });
        }

        if (!empty($postByFilter)) {
            $postsByparty->where('postType', $postByFilter);
        }

        if ($getTagedPost) {
            $userId = $partysId ?? Auth::user()->id;
            $userName = Party::find($userId) ? Party::find($userId)->name : (User::find($userId) ? User::find($userId)->firstName : null);
            $userNameLower = strtolower($userName);

            $postsByparty->where(function ($query) use ($userNameLower) {
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

        //PostFromCitizen
        $postByCitizen = PostByCitizen::with([
            'likes' => function ($query) use ($userId) {
                $query->where('LikeById', $userId);
            },
            'comments',
            'pollsByCitizenVote',
            'postByCitizenMetas:id,postByCitizenId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByCitizenDetails:id,postByCitizenId,pollOption,optionCount',
            'pollsByCitizenDetails.pollsByCitizenVotes:id,pollsByCitizenDetailsId',
            'user',
            'user.userDetails'
        ])
            ->whereNotIn('post_by_citizens.id', $reportedPost)
            ->orderBy('post_by_citizens.createdAt', 'desc');


        if ($keyword == '' && $activity == '' && $archieve == '' && $createdBy == '' && $getTagedPost == false) {
            $postByCitizen->orWhere('mention', 'like', "%$cityTown%")
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
            $postByCitizen->where('postTitle', 'ILIKE', "%$keyword%")
                ->orWhere('mention', 'like', "%$keyword")
                ->orWhere('hashTags', 'like', "%$keyword");
        }
        if (!empty($activity)) {
            $activityParts = explode('|', $activity);
            $activity = $activityParts[0];
            $postType = $activityParts[1];

            $postByCitizen->whereHas('user', function ($query) use ($activity, $postType) {
                $query->where('citizenId', $activity)->where('postType', $postType);
            })->orWhereHas('likes', function ($query) use ($activity, $postType) {
                $query->where('LikeById', $activity)->where('postType', $postType);
            })->orWhereHas('comments', function ($query) use ($activity, $postType) {
                $query->where('commentById', $activity)->where('postType', $postType);
            });
        }

        if (!empty($archieve)) {
            $postByCitizen->whereIn('id', $archieve);
        }
        if (!empty($createdBy)) {
            $postByCitizen->where('citizenId', $createdBy);
        }
        if (!empty($postByFilter)) {
            $postByCitizen->where('postType', $postByFilter);
        }
        if ($getTagedPost) {
            $userId = $partysId ?? Auth::user()->id;
            $userName = Party::find($userId) ? Party::find($userId)->name : (User::find($userId) ? User::find($userId)->firstName : null);
            $userNameLower = strtolower($userName);
            $userNameWithoutAt = str_replace('@', '', $userNameLower);

            $postByCitizen->where(function ($query) use ($userNameLower, $userNameWithoutAt) {
                $query->whereNotNull('mention')
                    ->where(function ($query) use ($userNameLower, $userNameWithoutAt) {
                        $query->whereRaw("LOWER(mention) LIKE LOWER(?)", ["%$userNameLower%"])
                            ->orWhereRaw("LOWER(mention) LIKE LOWER(?)", ["%$userNameWithoutAt%"]);
                    });
            });
        }
        $postsByLeaderPaginated = $postsByLeader->limit($limit)->get();
        $postsBypartyPaginated = $postsByparty->limit($limit)->get();
        $postByCitizenPaginated = $postByCitizen->limit($limit)->get();
        $combinedPosts = collect()
            ->merge($postByCitizenPaginated)
            ->merge($postsByLeaderPaginated)
            ->merge($postsBypartyPaginated);

        // return ($postsByLeaderPaginated);


        $combinedPosts = $combinedPosts->sortByDesc('createdAt');
        $filteredPosts = $combinedPosts->map(function ($post) use ($currentPage, $userId, $partysId, $leaderFollowers, $partyFollowers) {
            $userId = $partysId ?? Auth::user()->id;

            $url = env('APP_URL');
            $authorType = $post->authorType;
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=" . $authorType;

            $postByFullName = ($authorType == 'Citizen' || $authorType == 'Leader')
                ? (!empty($post->user) ? ($post->user->firstName . ' ' . $post->user->lastName) : '')
                : (!empty($post->party) ? $post->party->name : '');

            $creatorId = ($authorType == 'Citizen') ? 'citizenId' : (($authorType == 'Party') ? 'partyId' : 'leaderId');
            $createdBy = ($authorType == 'Citizen') ? $post->citizenId : (($authorType == 'Party') ? $post->partyId : $post->leaderId);
            $likes = $post->likes;
            $isLiked = $likes != '';
            $likedType = $isLiked ? $likes->likeType : null;
            $percentages = [];
            $imageUrls = $ideaDepartment = $postDescriptions = $pollOption = $optionCounts = [];
            $isUserVoted = false;
            $selectedOption = null;
            $isFollowing = false;
            $designation = '';
            switch ($authorType) {
                case 'Leader':
                    $metas = $post->postByLeaderMetas;
                    $pollDetails = $post->pollsByLeaderDetails;
                    $pollsVote = $post->pollsByLeaderVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByLeader;
                    $isEditable = !empty($post->user) ? ($post->user->id === Auth::user()->id) : false;
                    $isOwnPost = $userId == $post->leaderId;
                    $isFollowing = $leaderFollowers->contains($post->leaderId);
                    $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;

                    $designationName = optional($post->leader)->leaderElectedRole;
                    $leaderParty = (!empty($post->leader)) ? $post->leader->getLeaderCoreParty->party->nameAbbrevation : null;
                    $designation = $designationName . (!empty($leaderParty) ? " | " . $leaderParty : "");

                    // $designation = optional($post->leader)->leaderMinistry;
                    // $leaderParty = (!empty($post->leader)) ? $post->leader->getLeaderCoreParty->party->nameAbbrevation : null;
                    // $designation = $designation . (!empty($leaderParty) ? "|" . $leaderParty : "");

                    $address = '';
                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                case 'Citizen':
                    $metas = $post->postByCitizenMetas;
                    $pollDetails = $post->pollsByCitizenDetails;
                    $pollsVote = $post->pollsByCitizenVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByCitizen;
                    $isEditable = !empty($post->user) ? ($post->user->id === Auth::user()->id) : false;
                    $isOwnPost = $userId == $post->citizenId;
                    $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
                    $city = !empty($post->user->userAddress) ? $post->user->userAddress->cityTown : null;
                    $district = !empty($post->user->userAddress) ? $post->user->userAddress->district : null;
                    $address = $district . " " . $city;

                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                case 'Party':
                    $metas = $post->postByPartyMetas;
                    $pollDetails = $post->pollsByPartyDetails;
                    $pollsVote = $post->pollsByPartyVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByParty;
                    $isEditable = !empty($post->party) ? ($post->party->id === $partysId) : false;
                    $isOwnPost = $userId == $post->partyId;
                    $isFollowing = $partyFollowers->contains($post->partyId);
                    $profileImage = $post->party->logo;

                    $city = !empty($post->user->userAddress) ? $post->user->userAddress->city : null;
                    $district = !empty($post->user->userAddress) ? $post->user->userAddress->district : null;

                    $address = $district . " " . $city;

                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                default:
                    break;
            }

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
            $foundItem = '';
            if ($pollDetails) {
                $pollOption = $pollDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray();
                $optionCounts = $pollDetails->pluck('optionCount')->sortByDesc('optionCount')->toArray();

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
                if (count($pollsVote) > 0) {
                    $userIds = array_column($pollsVote, 'userId');
                    $userExists = in_array($userId, $userIds);
                    $userId = $partysId ?? Auth::user()->id;
                    $isUserVoted = false;
                    if ($userExists) {
                        $userData = collect($pollsVote)->first(function ($item) use ($userId) {
                            return $item['userId'] === $userId;
                        });

                        if ($userData !== null && isset($userData['selectedOption'])) {
                            $selectedOption = $userData['selectedOption'];
                            $isUserVoted = true;
                        }
                    }

                }

            }
            $complaintStatus = null;
            if ($post->postType == 'Complaints') {
                $complaintStatus = $metas->complaintStatus ?? null;
            }
            $formattedDate = ($post->createdAt !== null) ? $post->createdAt->diffForHumans() : 'N/A';

            return [
                'postURL' => $postURL,
                'postByName' => $postByFullName,
                'postId' => $post->id,
                "$creatorId" => $createdBy,
                'isLiked' => $isLiked,
                'designation' => $designation,
                "address" => $address,
                'postByProfilePicture' => $profileImage,
                'likedType' => $likedType,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'authorType' => $post->authorType,
                'image' => $imageUrls,
                'postCreatedAt' => $formattedDate,
                'ideaDepartment' => $ideaDepartment,
                'postDescriptions' => $postDescriptions,
                'pollOption' => $pollOption,
                'optionCount' => $percentages,
                'pollendDate' => optional($metas)->first()->PollendDate ?? null,
                'pollendTime' => optional($metas)->first()->pollendTime ?? null,
                'complaintLocation' => optional($metas)->first()->complaintLocation ?? null,
                'optionLength' => optional($pollDetails)->count() ?? 0,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'eventsLocation' => (!empty($events)) ? $events->pluck('eventsLocation')->first() : null,
                'eventStartDate' => (!empty($events)) ? $events->pluck('startDate')->first() : null,
                'eventsEndDate' => (!empty($events)) ? $events->pluck('endDate')->first() : null,
                'eventStartTime' => (!empty($events)) ? $events->pluck('startTime')->first() : null,
                'eventsEndTime' => (!empty($events)) ? $events->pluck('endTime')->first() : null,
                'IsEditable' => $isEditable,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'IsFollowing' => $isFollowing,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' => $isOwnPost,
                'createdBy' => $createdBy,
                'isCreatedByAdmin' => false,
                'isAds' => false,
                'sponserLink' => null,
                'isPublished' => $post->isPublished,
            ];
        })->reject(function ($post) {
            return !$post['isPublished'];
        });

        $authorType = '';
        $desiredTotal = $filteredPosts->count();
        $pagedPosts = $filteredPosts->forPage($currentPage, 5)->values();

        // if(!empty($featureAdsPost))
        // {
        //     $filteredPosts = $filteredPosts->merge($featureAdsPost);
        // }

        $combinedPosts = $filteredPosts;

        if (empty($archieve)) {
            $postsToRemove = Archive::pluck('postId')->toArray();
            $combinedPosts = $combinedPosts->reject(function ($post) use ($postsToRemove) {
                return in_array($post['postId'], $postsToRemove);
            });
        }

        if (!empty($createdBy) && $authorType == 'Party') {
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy) {
                return isset($post['partyId']) && $post['partyId'] == $createdBy;
            });
        }

        if ($postByFilter == 'Polls') {
            $currentTime = date("Y-m-d\TH:i:s\Z");
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy, $currentTime) {
                if ($post['postType'] == 'Polls') {
                    $pollEndDateTime = \Carbon\Carbon::parse("{$post['pollendDate']} {$post['pollendTime']}", 'UTC');
                    return $pollEndDateTime->isPast();
                }
            });
        }

        if ($postByFilter == 'Events') {
            $currentTime = date("Y-m-d\TH:i:s\Z");
            $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy, $currentTime) {
                if ($post['postType'] == 'Events') {
                    $eventsEndDateTime = \Carbon\Carbon::parse("{$post['eventsEndDate']} {$post['eventsEndTime']}", 'UTC');
                    return $eventsEndDateTime->isPast();
                }
            });
        }

        if (empty($activity)) {
            if ($postByFilter == '') {
                $currentTime = date("Y-m-d\TH:i:s\Z");
                $combinedPosts = $combinedPosts->filter(function ($post) use ($createdBy, $currentTime) {
                    if ($post['postType'] == 'Events') {
                        $eventsEndDateTime = \Carbon\Carbon::parse("{$post['eventsEndDate']} {$post['eventsEndTime']}", 'UTC');
                        return $eventsEndDateTime->isFuture();
                    } elseif ($post['postType'] == 'Polls') {
                        $pollEndDateTime = \Carbon\Carbon::parse("{$post['pollendDate']} {$post['pollendTime']}", 'UTC');
                        return $pollEndDateTime->isFuture();
                    }

                    return true;
                });
            }
        }
        $desiredTotal = $combinedPosts->count();
        $pagedPosts = $combinedPosts->forPage($currentPage, $perPage)->values();
        $combinedPosts = $combinedPosts->unique('postId')->sortByDesc('createdAt');

        if ($currentPage > 1) {
            $pagedPosts = collect($pagedPosts);
            $newPosts = $combinedPosts->filter(function ($post) {
                return is_array($post) && isset($post['createdAt']) && Carbon::parse($post['createdAt'])->diffInMinutes(now()) < 10;
            });

            $includedPostIds = [];
            $newPostsToAdd = collect();
            foreach ($newPosts as $newPost) {
                $postId = $newPost['postId'];

                if (!in_array($postId, $includedPostIds)) {
                    $insertedPage = self::determineInsertionPage($newPost['createdAt'], $perPage);
                    $newPostsToAdd->push($newPost);
                    $includedPostIds[] = $postId;
                }
            }
            $pagedPosts = $pagedPosts->merge($newPostsToAdd);
            $pagedPosts = $pagedPosts->values();
        }

        if (count($featureAdsPost) > 0) {
            $adPosts = $featureAdsPost;
            $adIndex = ($currentPage - 1) % count($adPosts);

            $fullPages = floor(($currentPage - 1) / $perPage);
            $postsBeforeAd = $fullPages * $perPage;

            $insertIndex = $postsBeforeAd + $adIndex + 1;

            $pagedPosts->splice($insertIndex, 0, [$adPosts[$adIndex]]);
        }

        //pushed ads end
        $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
        return $list;
    }
    public static function determineInsertionPage($postCreatedAt, $perPage)
    {
        $minutesAgo = now()->diffInMinutes($postCreatedAt);

        $insertedPage = min(ceil($minutesAgo / $perPage) + 1, 1);

        return $insertedPage;
    }


    public static function getAds($currentPage, $partysId, $keyword, $activity, $archieve, $createdBy, $postByFilter)
    {
        // return $createdBy;
        $userId = $partysId ?? Auth::user()->id;
        $leaderFollowers = LeaderFollowers::where('followerId', $userId)->pluck('leaderId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userTags = UserAddress::where('userId', $userId)->first();
        $cityTown = $userTags->cityTown ?? null;
        $state = $userTags->state ?? null;
        $district = $userTags->district ?? null;
        // $perPage = env('PAGINATION_PER_PAGE', 5);
        $limit = env('MAX_POSTS_PER_SOURCE', 500);

        $perPage = 5;
        $partyFollowers = PartyFollowers::where('followerId', $userId)->pluck('partyId');
        $seenPostIds = PostSeen::where('userId', $userId)->pluck('postId');
        $userPartyIds = PartyLogin::where('userId', $userId)->pluck('partyId');
        $fullname = Auth::user()->firstName . ' ' . Auth::user()->lastName;
        $userName = Auth::user()->userName;
        $getTagedPost = false;
        $consituencyFollowers = UserFollowerTag::where('userId', $userId)->pluck('followedTags');
        $loggedInUserId = Auth::user()->id;

        $reportedPost = ReportPost::where('reportedBy', $userId)->pluck('postId');

        $userAddress = UserAddress::where('userId', $userId)->first();

        $userDetails = UserDetails::where('userId', $userId)->first();

        $stateId = State::where('name', $userAddress->state)->value('id');
        $reportedPost = ReportPost::where('reportedBy', $userId)->pluck('postId');

        $userDetailsIsEmpty = empty($userDetails) ? true : false;
        $adsIds = AdTarget::where('stateId', $stateId)
            ->orWhere('constituency', $userDetailsIsEmpty ? null : $userDetails->assemblyId)
            ->orWhere('constituency', $userDetailsIsEmpty ? null : $userDetails->loksabhaId)
            ->pluck('adId')
            ->unique()
            ->toArray();

        $currentDateTime = Carbon::now('Asia/Kolkata');

        $getRunnableAdds = Ad::whereIn('id', $adsIds)
            ->where('status', 'Active')
            ->where('startDate', '<=', $currentDateTime->toDateString())
            ->where(function ($query) use ($currentDateTime) {
                $query->where(function ($query) use ($currentDateTime) {
                    $query->where('startDate', '=', $currentDateTime->toDateString())
                        ->where('startTime', '<=', $currentDateTime->toTimeString());
                })
                    ->orWhere(function ($query) use ($currentDateTime) {
                        $query->where('endDate', '>', $currentDateTime->toDateString())
                            ->orWhere(function ($query) use ($currentDateTime) {
                                $query->whereDate('endDate', $currentDateTime->toDateString())
                                    ->where('endTime', '>', $currentDateTime->toTimeString());
                            });
                    });
            })
            ->orWhereIn('createdBy', $leaderFollowers)
            ->orWhereIn('createdBy', $partyFollowers)
            ->pluck('id')
            ->toArray();

        $adPost = AdPost::whereIn('adsId', $getRunnableAdds)->pluck('postId')->toArray();

        $postsByLeader = PostByLeader::with([
            'likes' => function ($query) use ($userId) {
                $query->where('LikeById', $userId);
            },
            'comments',
            'pollsByLeaderVote',
            'postByLeaderMetas:id,postByLeaderId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByLeaderDetails:id,postByLeaderId,pollOption,optionCount',
            'pollsByLeaderDetails.pollsByLeaderVotes:id,pollsByLeaderDetailsId',
            'eventsByLeader:id,postByLeaderId,eventsLocation,startDate,endDate,startTime,endTime',
            'user',
            'leader',
            'user.userDetails',
            'ad.AdsDetails',

        ])
            ->whereIn('post_by_leaders.id', $adPost)
            ->whereNotIn('post_by_leaders.id', $reportedPost)

            ->orderBy('post_by_leaders.createdAt', 'desc');


        $postsByparty = PostByParty::with([
            'party',
            'likes' => function ($query) use ($userId) {
                $query->where('LikeById', $userId);
            },
            'comments',
            'pollsByPartyVote',
            'postByPartyMetas:id,postByPartyId,ideaDepartment,postDescriptions,imageUrl1,imageUrl2,imageUrl3,imageUrl4,PollendDate,pollendTime,complaintLocation',
            'pollsByPartyDetails:id,postByPartyId,pollOption,optionCount',
            'pollsByPartyDetails.pollsByPartyVotes:id,pollsByPartyDetailsId',
            'eventsByParty:id,postByPartyId,eventsLocation,startDate,endDate,startTime,endTime',
            'ad.AdsDetails',
        ])
            ->whereNotIn('post_by_parties.id', $reportedPost)
            ->whereIn('post_by_parties.id', $adPost)
            ->orderBy('post_by_parties.createdAt', 'desc');





        //PostFromCitizen

        $postsByLeaderPaginated = $postsByLeader->limit(10)->get();
        $postsBypartyPaginated = $postsByparty->limit(10)->get();
        $combinedPosts = collect()
            ->merge($postsByLeaderPaginated)
            ->merge($postsBypartyPaginated);

        // return ($postsByLeaderPaginated);


        $combinedPosts = $combinedPosts->sortByDesc('createdAt');
        $filteredPosts = $combinedPosts->map(function ($post) use ($currentPage, $userId, $partysId, $leaderFollowers, $partyFollowers) {
            $userId = $partysId ?? Auth::user()->id;

            $url = env('APP_URL');
            $authorType = $post->authorType;
            $encryptedPostId = EncryptionHelper::encryptString($post->id);
            $postURL = $url . '/sharedPost/' . $encryptedPostId . "?postByType=" . $authorType;

            $postByFullName = ($authorType == 'Citizen' || $authorType == 'Leader')
                ? (!empty($post->user) ? ($post->user->firstName . ' ' . $post->user->lastName) : '')
                : (!empty($post->party) ? $post->party->name : '');

            $creatorId = ($authorType == 'Citizen') ? 'citizenId' : (($authorType == 'Party') ? 'partyId' : 'leaderId');
            $createdBy = ($authorType == 'Citizen') ? $post->citizenId : (($authorType == 'Party') ? $post->partyId : $post->leaderId);
            $likes = $post->likes;
            $isLiked = $likes != '';
            $likedType = $isLiked ? $likes->likeType : null;
            $percentages = [];
            $imageUrls = $ideaDepartment = $postDescriptions = $pollOption = $optionCounts = [];
            $isUserVoted = false;
            $selectedOption = null;
            $isFollowing = false;
            $designation = '';
            $adsUrl = '';
            switch ($authorType) {
                case 'Leader':
                    $metas = $post->postByLeaderMetas;
                    $pollDetails = $post->pollsByLeaderDetails;
                    $pollsVote = $post->pollsByLeaderVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByLeader;
                    $isEditable = !empty($post->user) ? ($post->user->id === Auth::user()->id) : false;
                    $isOwnPost = $userId == $post->leaderId;
                    $isFollowing = $leaderFollowers->contains($post->leaderId);
                    $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;

                    $designationName = optional($post->leader)->leaderElectedRole;
                    $leaderParty = (!empty($post->leader)) ? $post->leader->getLeaderCoreParty->party->nameAbbrevation : null;
                    $designation = $designationName . (!empty($leaderParty) ? " | " . $leaderParty : "");
                    $adsUrl = $post->ad !== null ? $post->ad->AdsDetails->url : null;

                    // $designation = optional($post->leader)->leaderMinistry;
                    // $leaderParty = (!empty($post->leader)) ? $post->leader->getLeaderCoreParty->party->nameAbbrevation : null;
                    // $designation = $designation . (!empty($leaderParty) ? "|" . $leaderParty : "");

                    $address = '';
                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                case 'Citizen':
                    $metas = $post->postByCitizenMetas;
                    $pollDetails = $post->pollsByCitizenDetails;
                    $pollsVote = $post->pollsByCitizenVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByCitizen;
                    $isEditable = !empty($post->user) ? ($post->user->id === Auth::user()->id) : false;
                    $isOwnPost = $userId == $post->citizenId;
                    $profileImage = !empty($post->user->userDetails) ? $post->user->userDetails->profileImage : null;
                    $city = !empty($post->user->userAddress) ? $post->user->userAddress->cityTown : null;
                    $district = !empty($post->user->userAddress) ? $post->user->userAddress->district : null;
                    $address = $district . " " . $city;

                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                case 'Party':
                    $metas = $post->postByPartyMetas;
                    $pollDetails = $post->pollsByPartyDetails;
                    $pollsVote = $post->pollsByPartyVote;
                    $pollsVote = ($pollsVote !== null) ? $pollsVote->toArray() : [];
                    $events = $post->eventsByParty;
                    $isEditable = !empty($post->party) ? ($post->party->id === $partysId) : false;
                    $isOwnPost = $userId == $post->partyId;
                    $isFollowing = $partyFollowers->contains($post->partyId);
                    $profileImage = $post->party->logo;
                    $adsUrl = $post->ad !== null ? $post->ad->AdsDetails->url : null;
                    $city = !empty($post->user->userAddress) ? $post->user->userAddress->city : null;
                    $district = !empty($post->user->userAddress) ? $post->user->userAddress->district : null;
                    $address = $district . " " . $city;

                    if ($post->anonymous === true) {
                        $postByFullName = 'Anonymous';
                        $profileImage = null;
                    }
                    break;
                default:
                    break;
            }

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
            $foundItem = '';
            if ($pollDetails) {
                $pollOption = $pollDetails->sortByDesc('optionCount')->pluck('pollOption')->toArray();
                $optionCounts = $pollDetails->pluck('optionCount')->sortByDesc('optionCount')->toArray();

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
                if (count($pollsVote) > 0) {
                    $userIds = array_column($pollsVote, 'userId');
                    $userExists = in_array($userId, $userIds);
                    $userId = $partysId ?? Auth::user()->id;
                    $isUserVoted = false;
                    if ($userExists) {
                        $userData = collect($pollsVote)->first(function ($item) use ($userId) {
                            return $item['userId'] === $userId;
                        });

                        if ($userData !== null && isset($userData['selectedOption'])) {
                            $selectedOption = $userData['selectedOption'];
                            $isUserVoted = true;
                        }
                    }

                }

            }
            $complaintStatus = null;
            if ($post->postType == 'Complaints') {
                $complaintStatus = $metas->complaintStatus ?? null;
            }
            $formattedDate = ($post->createdAt !== null) ? $post->createdAt->diffForHumans() : 'N/A';

            return [
                'postURL' => $postURL,
                'postByName' => $postByFullName,
                'postId' => $post->id,
                "$creatorId" => $createdBy,
                'isLiked' => $isLiked,
                'designation' => $designation,
                "address" => $address,
                'postByProfilePicture' => $profileImage,
                'likedType' => $likedType,
                'postType' => $post->postType,
                'postTitle' => $post->postTitle,
                'likesCount' => $post->likesCount,
                'commentsCount' => $post->commentsCount,
                'shareCount' => $post->shareCount,
                'anonymous' => $post->anonymous,
                'hashTags' => $post->hashTags,
                'mention' => $post->mention,
                'authorType' => $post->authorType,
                'image' => $imageUrls,
                'postCreatedAt' => $formattedDate,
                'ideaDepartment' => $ideaDepartment,
                'postDescriptions' => $postDescriptions,
                'pollOption' => $pollOption,
                'optionCount' => $percentages,
                'pollendDate' => optional($metas)->first()->PollendDate ?? null,
                'pollendTime' => optional($metas)->first()->pollendTime ?? null,
                'complaintLocation' => optional($metas)->first()->complaintLocation ?? null,
                'optionLength' => optional($pollDetails)->count() ?? 0,
                'IsVoted' => $isUserVoted,
                'selectedOption' => $selectedOption,
                'eventsLocation' => (!empty($events)) ? $events->pluck('eventsLocation')->first() : null,
                'eventStartDate' => (!empty($events)) ? $events->pluck('startDate')->first() : null,
                'eventsEndDate' => (!empty($events)) ? $events->pluck('endDate')->first() : null,
                'eventStartTime' => (!empty($events)) ? $events->pluck('startTime')->first() : null,
                'eventsEndTime' => (!empty($events)) ? $events->pluck('endTime')->first() : null,
                'IsEditable' => $isEditable,
                'createdAt' => $post->createdAt,
                'currentPage' => $currentPage,
                'IsFollowing' => $isFollowing,
                'complaintStatus' => $complaintStatus,
                'isOwnPost' => $isOwnPost,
                'createdBy' => $createdBy,
                'isCreatedByAdmin' => false,
                'isAds' => $post->isAds,
                'sponserLink' => $adsUrl,
                'isPublished' => $post->isPublished,
            ];
        })->reject(function ($post) {
            return !$post['isPublished'];
        });
        return $filteredPosts;



    }

}