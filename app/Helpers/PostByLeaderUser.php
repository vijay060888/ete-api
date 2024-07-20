<?php

namespace App\Helpers;

use App\Models\EventsByLeader;
use App\Models\MasterHashTag;
use App\Models\MasterMention;
use App\Models\PollsByCitizenDetails;
use App\Models\PollsByLeaderDetails;
use App\Models\PostByCitizen;
use App\Models\PostByCitizenMeta;
use App\Models\PostByLeader;
use App\Models\PostByLeaderMeta;
use App\Models\StoryByCitizen;
use App\Models\StoryByLeader;
use App\Models\User;
use App\Post; 
use Auth;

class PostByLeaderUser
{
    public static function createPollPost($leaderUserId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $polls = $postDetails['polls'];
        $pollsEndTime = $postDetails['pollEndTime'];
        $pollsEndDate = $postDetails['pollEndDate'];
        $anonymous = $postDetails['anonymous'];
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

        if ($hashTags !== null) {
            $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
            
            foreach ($hashTagArray as $hashTagsList) {
                $hashTagUse = MasterHashTag::where('hashtag', $hashTagsList)->first();
        
                if ($hashTagUse) {
                    $hashTagUse->increment('hashtagsUseCount');
                } else {
                    MasterHashTag::create([
                        'hashtag' => $hashTagsList,
                        'id' => \DB::raw('gen_random_uuid()'),
                        'createdBy' => $leaderUserId,
                        'updatedBy' => $leaderUserId,
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ]);
                }
            }
        }
         $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
        foreach ($mentionArray as $mentions) {
            MasterMention::updateOrInsert(
                ['mention' => $mentions],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'postByType' => $userType,
                    'typeId' => $leaderUserId,
                    'createdBy' => $leaderUserId,
                    'updatedBy' => $leaderUserId,
                    'createdAt' => now(),
                    'updatedAt' => now(),

                ]
            );
        }

        $user = User::where('id', $leaderUserId)->select('privacy')->first();
       
        // if($anonymous=="false"){
        //     $anonymous = ($user->privacy == 1) ? true : false;
        // } 
        $anonymous = ($userType == 'Citizen' && $anonymous === true) || ($userType == 'Citizen' && $anonymous === false && $user->privacy == 1);        


        $postModel = $userType === 'Leader' ? PostByLeader::class : PostByCitizen::class;
        $postModelMeta = $userType === 'Leader' ? PostByLeaderMeta::class : PostByCitizenMeta::class;
        $pollsByDetailsModel = $userType === 'Leader' ? PollsByLeaderDetails::class : PollsByCitizenDetails::class;

        $userId = $userType === 'Leader' ? 'leaderId' : 'citizenId';
        $postById = $userType === 'Leader' ? 'postByLeaderId' : 'postByCitizenId';
        $postDetails = [
            "authorType" => $userType,
            "$userId" => $leaderUserId,
            "postType" => "Polls",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'isPublished' => $isPublished,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'sentiment' => $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        
        $postByLeaderMetaData = [
            "$postById" => $post->id,
            'postDescriptions' => $description,
            'PollendDate' => $pollsEndDate,
            'pollendTime' => $pollsEndTime,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postModelMeta::create($postByLeaderMetaData);
        foreach($polls as $poll){
            $pollDetailsData =[
            "$postById" => $post->id,
            'pollOption' => $poll['option'],
            'optionCount'=>0,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
            ];
            $pollsByDetailsModel::create($pollDetailsData);
        }
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];
        // return "Polls Created Successfully";
    }

    public static function createComplaintPost($leaderUserId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $location=$postDetails['location'];
        $anonymous = $postDetails['anonymous'];
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

        if ($hashTags !== null) {
            $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
            
            foreach ($hashTagArray as $hashTagsList) {
                $hashTagUse = MasterHashTag::where('hashtag', $hashTagsList)->first();
        
                if ($hashTagUse) {
                    $hashTagUse->increment('hashtagsUseCount');
                } else {
                    MasterHashTag::create([
                        'hashtag' => $hashTagsList,
                        'id' => \DB::raw('gen_random_uuid()'),
                        'createdBy' => $leaderUserId,
                        'updatedBy' => $leaderUserId,
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ]);
                }
            }
        }
        
        $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
        foreach ($mentionArray as $mentions) {
            MasterMention::updateOrInsert(
                ['mention' => $mentions],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'postByType' => $userType,
                    'typeId' => $leaderUserId,
                    'createdBy' => $leaderUserId,
                    'updatedBy' => $leaderUserId,
                    'createdAt' => now(),
                    'updatedAt' => now(),

                ]
            );
        }

        $user = User::where('id', $leaderUserId)->select('privacy')->first();
        
        // if($anonymous=="false"){
        //     $anonymous = ($user->privacy == 1) ? true : false;
        // } 
       
        $anonymous = ($userType == 'Citizen' && $anonymous === true) || ($userType == 'Citizen' && $anonymous === false && $user->privacy == 1);        

        $postModel = $userType === 'Leader' ? PostByLeader::class : PostByCitizen::class;
        $postModelMeta = $userType === 'Leader' ? PostByLeaderMeta::class : PostByCitizenMeta::class;
        $userId = $userType === 'Leader' ? 'leaderId' : 'citizenId';
        $postById = $userType === 'Leader' ? 'postByLeaderId' : 'postByCitizenId';
        $postDetails = [
            "authorType" => $userType,
            "$userId" => $leaderUserId,
            "postType" => "Complaint",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'isPublished' =>   $isPublished,
            'sentiment' =>   $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage,
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        
        $postByLeaderMetaData = [
            "$postById" => $post->id,
            'postDescriptions' => $description,
            "complaintLocation" =>$location,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByLeaderMeta = $postModelMeta::create($postByLeaderMetaData);
        $i = 1;
        if($media!='')
        {
           foreach ($media as $medias) {
            $postModelMeta::where('id', $postByLeaderMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        } 
    }
        
        $response = ($isPublished == true) 
    ? "Post Created Successfully"
    : "Post to be verified";

    // return $response;
    return ['response' => $response, 'lastInsertedId' => $lastInsertedId];

    }

    public static function createMultimediaPost($leaderUserId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $anonymous = $postDetails['anonymous'];
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];
        if ($hashTags !== null) {
            $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
            
            foreach ($hashTagArray as $hashTagsList) {
                $hashTagUse = MasterHashTag::where('hashtag', $hashTagsList)->first();
        
                if ($hashTagUse) {
                    $hashTagUse->increment('hashtagsUseCount');
                } else {
                    MasterHashTag::create([
                        'hashtag' => $hashTagsList,
                        'id' => \DB::raw('gen_random_uuid()'),
                        'createdBy' => $leaderUserId,
                        'updatedBy' => $leaderUserId,
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ]);
                }
            }
        }
        
        $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
        foreach ($mentionArray as $mentions) {
            MasterMention::updateOrInsert(
                ['mention' => $mentions],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'postByType' => $userType,
                    'typeId' => $leaderUserId,
                    'createdBy' => $leaderUserId,
                    'updatedBy' => $leaderUserId,
                    'createdAt' => now(),
                    'updatedAt' => now(),

                ]
            );
        }

        $user = User::where('id', $leaderUserId)->select('privacy')->first();

        // if($anonymous=="false"){
        //     $anonymous = ($user->privacy == 1) ? true : false;
        // } 
        $anonymous = ($userType == 'Citizen' && $anonymous === true) || ($userType == 'Citizen' && $anonymous === false && $user->privacy == 1);        
        $postModel = $userType === 'Leader' ? PostByLeader::class : PostByCitizen::class;
        $postModelMeta = $userType === 'Leader' ? PostByLeaderMeta::class : PostByCitizenMeta::class;
        $userId = $userType === 'Leader' ? 'leaderId' : 'citizenId';
        $postById = $userType === 'Leader' ? 'postByLeaderId' : 'postByCitizenId';
        $postDetails = [
            "authorType" => $userType,
            "$userId" => $leaderUserId,
            "postType" => "MultiMedia",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'isPublished' => $isPublished,
            'sentiment' => $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage,
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByLeaderMetaData = [
            "$postById" => $post->id,
            'postDescriptions' => $description,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByLeaderMeta = $postModelMeta::create($postByLeaderMetaData);
        $i = 1;
        if($media!='')
        {
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByLeaderMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }

    $response = ($isPublished == true) 
    ? "Post Created Successfully"
    : "Post to be verified";

    // return $response;
    return ['response' => $response, 'lastInsertedId' => $lastInsertedId];
    
        
    }

    public static function createEventPost($leaderUserId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $location=$postDetails['location'];
        $anonymous = $postDetails['anonymous'];
        $eventEndDate = $postDetails['eventEndDate'];
        $eventStartDate = $postDetails['eventStartDate'];
        $eventEndTime = $postDetails['eventEndTime'];
        $eventStartTime = $postDetails['eventStartTime'];
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

        if ($hashTags !== null) {
            $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
            
            foreach ($hashTagArray as $hashTagsList) {
                $hashTagUse = MasterHashTag::where('hashtag', $hashTagsList)->first();
        
                if ($hashTagUse) {
                    $hashTagUse->increment('hashtagsUseCount');
                } else {
                    MasterHashTag::create([
                        'hashtag' => $hashTagsList,
                        'id' => \DB::raw('gen_random_uuid()'),
                        'createdBy' => $leaderUserId,
                        'updatedBy' => $leaderUserId,
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ]);
                }
            }
        }
        
        $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
        foreach ($mentionArray as $mentions) {
            MasterMention::updateOrInsert(
                ['mention' => $mentions],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'postByType' => $userType,
                    'typeId' => $leaderUserId,
                    'createdBy' => $leaderUserId,
                    'updatedBy' => $leaderUserId,
                    'createdAt' => now(),
                    'updatedAt' => now(),

                ]
            );
        }

        $user = User::where('id', $leaderUserId)->select('privacy')->first();
       
        // if($anonymous=="false"){
        //     $anonymous = ($user->privacy == 1) ? true : false;
        // } 
        $anonymous = ($userType == 'Citizen' && $anonymous === true) || ($userType == 'Citizen' && $anonymous === false && $user->privacy == 1);        

        $postModel = PostByLeader::class ;
        $postModelMeta =  PostByLeaderMeta::class;
        $postDetails = [
            "authorType" => $userType,
            "leaderId" => $leaderUserId,
            "postType" => "Events",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'isPublished' =>$isPublished,
            'sentiment' =>$sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByLeaderMetaData = [
            "postByLeaderId" => $post->id,
            'postDescriptions' => $description,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByLeaderMeta = $postModelMeta::create($postByLeaderMetaData);
        $i = 1;
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByLeaderMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
        $eventsDetails = [
            "postByLeaderId" => $post->id,
            "eventsLocation" => $location,
            "startDate" => $eventStartDate,
            "endDate" => $eventEndDate,
            "startTime" => $eventStartTime,
            "endTime" => $eventEndTime,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];
        EventsByLeader::create($eventsDetails);
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];
        // return "Events Created Successfully";
    }
    public static function createIdeaPost($leaderUserId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $department=$postDetails['department'];
        $anonymous = $postDetails['anonymous'];
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

        if ($hashTags !== null) {
            $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
            
            foreach ($hashTagArray as $hashTagsList) {
                $hashTagUse = MasterHashTag::where('hashtag', $hashTagsList)->first();
        
                if ($hashTagUse) {
                    $hashTagUse->increment('hashtagsUseCount');
                } else {
                    MasterHashTag::create([
                        'hashtag' => $hashTagsList,
                        'id' => \DB::raw('gen_random_uuid()'),
                        'createdBy' => $leaderUserId,
                        'updatedBy' => $leaderUserId,
                        'createdAt' => now(),
                        'updatedAt' => now(),
                    ]);
                }
            }
        }
        
        $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
        foreach ($mentionArray as $mentions) {
            MasterMention::updateOrInsert(
                ['mention' => $mentions],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'postByType' => $userType,
                    'typeId' => $leaderUserId,
                    'createdBy' => $leaderUserId,
                    'updatedBy' => $leaderUserId,
                    'createdAt' => now(),
                    'updatedAt' => now(),

                ]
            );
        }

        $user = User::where('id', $leaderUserId)->select('privacy')->first();
        
        // if($anonymous=="false"){
        //     $anonymous = ($user->privacy == 1) ? true : false;
        // } 
        $anonymous = ($userType == 'Citizen' && $anonymous === true) || ($userType == 'Citizen' && $anonymous === false && $user->privacy == 1);        

        $postModel = $userType === 'Leader' ? PostByLeader::class : PostByCitizen::class;
        $postModelMeta = $userType === 'Leader' ? PostByLeaderMeta::class : PostByCitizenMeta::class;
        $userId = $userType === 'Leader' ? 'leaderId' : 'citizenId';
        $postById = $userType === 'Leader' ? 'postByLeaderId' : 'postByCitizenId';
        $postDetails = [
            "authorType" => $userType,
            "$userId" => $leaderUserId,
            "postType" => "Idea",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'isPublished' => $isPublished,
            'sentiment' => $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByLeaderMetaData = [
            "$postById" => $post->id,
            'postDescriptions' => $description,
            "ideaDepartment" =>$department,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByLeaderMeta = $postModelMeta::create($postByLeaderMetaData);
        $i = 1;
       if($media!='')
        {
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByLeaderMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }
        $response = ($isPublished == true) 
    ? "Post Created Successfully"
    : "Post to be verified";

    // return $response;
    return ['response' => $response, 'lastInsertedId' => $lastInsertedId];

    }
    public static function createStoryPost($leaderUserId,$userType,$storyContent,$storytext)
    {
        $storyModel = $userType === 'Leader' ? StoryByLeader::class : StoryByCitizen::class;
        $userId = $userType === 'Leader' ? 'leaderId' : 'citizenId';
        $storyData=[
            "$userId" => Auth::user()->id,
             "storyContent" => $storyContent,
             "storytext" => $storytext,
             'createdBy' => $leaderUserId,
             'updatedBy' => $leaderUserId,
             'createdAt' => now(),
             'updatedAt' => now(),
        ];
        $storyModel::create($storyData);
        return "Story Added";
    }

}