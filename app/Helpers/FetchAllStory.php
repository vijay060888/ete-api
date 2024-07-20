<?php


namespace App\Helpers;

use App\Models\LeaderFollowers;
use App\Models\PartyFollowers;
use App\Models\StoryByLeader;
use App\Models\StoryByLeaderViews;
use App\Models\StoryByParty;
use App\Models\StoryByPartyViews;
use App\Models\User;
use Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use App\Models\Party;

class FetchAllStory
{


    public static function getStoryFromLeaders($userId)
    {
        $leaderFollowers = LeaderFollowers::where('followerId', $userId)->pluck('leaderId');
        $twentyFourHoursAgo = Carbon::now()->subHours(24);
        $stories = StoryByLeader::whereIn('leaderId', $leaderFollowers)
            // ->where('leaderId', $userId)
            ->where('createdAt', '>=', $twentyFourHoursAgo)
            ->with(['user', 'user.userDetails'])
            ->orderBy('createdAt','desc')
            ->get();
        $formattedLeaderStories = $stories->map(function ($story) {
            $userId = Auth::user()->id;
            $name = $story->user->firstName . " " . $story->user->lastName;
            $existingView = StoryByLeaderViews::where('storyByLeaderId', $story->id)
                ->where('viewedBy', $userId)
                ->first();
            $createdAtDifference = $story->createdAt->diffForHumans();
            
            $isView = $existingView ? true : false;
            $storyCreatedBy = $story->user->id;
            $isEditable = $storyCreatedBy == $userId ? true : false;
            $formattedStory = [
                'story_id' => $story->id,
                'userId' => $story->user->id,
                'profilePicture' => $story->user->userDetails->profileImage,
                'name' => $name,
                'authorType' => 'Leader',
                'createdAt' => $createdAtDifference,
                'isView' => $isView,
                'story_image' => $story->storyContent,
                'storytext' => $story->storytext,
                'isEditable' => $isEditable
            ];

            return $formattedStory;
        });
        return $formattedLeaderStories;
    }


    public static function getStoryFromParties($userId)
    {
        $partyFollowers = PartyFollowers::where('followerId', $userId)->pluck('partyId');
        $twentyFourHoursAgo = Carbon::now()->subHours(24);
        $stories = StoryByParty::whereIn('partyId', $partyFollowers)
            ->where('createdAt', '>=', $twentyFourHoursAgo)
            ->with(['parties'])
            ->orderBy('createdAt','desc')
            ->get();
            // ->paginate($perPage, ['*'], 'page', $page);

        $formattedPartyStories = $stories->map(function ($story) {
            $userId = Auth::user()->id;
            $name = $story->parties->name;
            $existingView = StoryByPartyViews::where('storyByPartyId', $story->id)
                ->where('viewedBy', $userId)
                ->first();
            $createdAtDifference = $story->createdAt->diffForHumans();

            $isView = $existingView ? true : false;
            $formattedStory = [
                'story_id' => $story->id,
                'userId' => $story->parties->id,
                'profilePicture' => $story->parties->logo,
                'name' => $name,
                'isView' => $isView,
                'createdAt' => $createdAtDifference,
                'authorType' => 'Party',
                'story_image' => $story->storyContent,
                'storytext' => $story->storytext,
                'isEditable' => false
            ];

            return $formattedStory;
        });
        return $formattedPartyStories;
    }


    public static function getOwnStory($userId, $authorType)
    {
        $twentyFourHoursAgo = Carbon::now()->subHours(24);
        if ($authorType === 'Leader') {
            $stories = StoryByLeader::where('leaderId', $userId)
                ->where('createdAt', '>=', $twentyFourHoursAgo)
                ->with(['user', 'user.userDetails'])
                ->orderBy('createdAt','desc')
                ->get();
        } elseif ($authorType === 'Party') {
            $stories = StoryByParty::where('partyId', $userId)
                ->where('createdAt', '>=', $twentyFourHoursAgo)
                ->with(['parties'])
                ->orderBy('createdAt','desc')
                ->get();
        }

        if ($stories->isNotEmpty()) {
            $formattedStories = $stories->map(function ($story) use ($userId, $authorType) {
                $name = ($authorType === 'Leader') ? $story->user->firstName . " " . $story->user->lastName : $story->parties->name;
                $createdAtDifference = $story->createdAt->diffForHumans();

                $isEditable = false;
                
                if ($authorType === "Leader") {
                    $isEditable = $story->user->id == $userId;
                } elseif ($authorType === "Party") {
                    $isEditable = $story->parties->id == $userId;
                }
                $formattedStory = [
                    'story_id' => $story->id,
                    'userId' => $authorType === 'Leader' ? $story->user->id : $story->parties->id,
                    'profilePicture' => ($authorType === 'Leader') ? $story->user->userDetails->profileImage : $story->parties->logo,
                    'name' => $name,
                    'isView' => false,
                    'authorType' => $authorType,
                    'createdAt' => $createdAtDifference,
                    'story_image' => $story->storyContent,
                    'storytext' => $story->storytext,
                    'isEditable' => $isEditable
                ];

                return $formattedStory;
            });
        } else {
            $formattedStories = null;
        }

        return $formattedStories;
    }

    public static function getAllStory($userId)
    {
        $sources = [
            'getStoryFromLeaders',
            'getStoryFromParties',
        ];

        $combinedStories = collect([]);
        foreach ($sources as $source) {
            $stories = self::$source($userId);
            if ($stories->isNotEmpty()) {
                $combinedStories = $combinedStories->concat($stories);
            }
        }
        $stories = $combinedStories;
        return ($stories);
    }

public static function getCombinedStories($userId, $authorType, $page = 1, $perPage = 5)
{
    $ownStory = self::getOwnStory($userId, $authorType);
    $otherStories = self::getAllStory($userId);
    $usersWithStories = [];
    $seenStoryIds = [];
    $desiredTotal = $page * $perPage;
    $rolename = Auth::user()->getRoleNames()[0];
    $isOwnStoryExist = false;

    // User's own stories
    if ($ownStory !== null) {
        $isOwnStoryExist = true;
        $ownStoryArray = $ownStory->toArray();
        $userStories = [
            'user_id' => $userId,
            'user_image' => $ownStoryArray[0]['profilePicture'],
            'user_name' => $ownStoryArray[0]['name'],
        ];

        if (!empty($ownStoryArray)) {
            $userStories['stories'] = $ownStoryArray;
        }

        $usersWithStories[] = $userStories;

        $seenStoryIds = array_merge($seenStoryIds, array_column($ownStoryArray, 'story_id'));
    }

    $otherUserStories = [];
    foreach ($otherStories as $story) {
        if (!in_array($story['story_id'], $seenStoryIds)) {
            $existingUserIndex = array_search($story['userId'], array_column($otherUserStories, 'user_id'));

            if ($existingUserIndex !== false) {
                $otherUserStories[$existingUserIndex]['stories'][] = $story;
            } else {
                $userStories = [
                    'user_id' => $story['userId'],
                    'user_image' => $story['profilePicture'],
                    'user_name' => $story['name'],
                    'stories' => [$story],
                ];

                if (!empty($story)) {
                    $userStories['stories'] = [$story];
                }

                $otherUserStories[] = $userStories;

                $seenStoryIds[] = $story['story_id'];
            }
        }
    }

    $pagination = new LengthAwarePaginator($otherUserStories, count($otherUserStories), $perPage, $page);
    return [
        'isOwnStoryExist' => $isOwnStoryExist,
        'ownStories' => $usersWithStories,
        'otherStories' => $pagination,
    ];
}



    
}