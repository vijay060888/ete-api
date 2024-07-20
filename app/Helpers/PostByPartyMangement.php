<?php

namespace App\Helpers;


use App\Models\EventsByParty;
use App\Models\MasterHashTag;
use App\Models\MasterMention;
use App\Models\Party;
use App\Models\PollsByPartyDetails;
use App\Models\PostByParty;
use App\Models\PostByPartyMeta;
use App\Models\StoryByParty;
use Auth;

class PostByPartyMangement
{
    public static function createPollPost($partyId, $postDetails, $media, $userType)
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

        $leaderUserId = Auth::user()->id;
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

        
        $party=Party::where('id',$partyId)->first();      
        $postModel =  PostByParty::class;
        $postModelMeta = PostByPartyMeta::class;
        $pollsByDetailsModel = PollsByPartyDetails::class;
        $postDetails = [
            "authorType" => $userType,
            "partyId" => $party->id,
            "postType" => "Polls",
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
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByLeaderMetaData = [
            "postByPartyId" => $post->id,
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
            "postByPartyId" => $post->id,
            'pollOption' => $poll['option'],
            'optionCount'=>0,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
            ];
            $pollsByDetailsModel::create($pollDetailsData);
        }
     
        // return "Polls Created Successfully";
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];

    }

    public static function createComplaintPost($partyId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $location=$postDetails['location'];
        $anonymous = $postDetails['anonymous'];
        $leaderUserId = Auth::user()->id;
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

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

        // $user = User::where('id', $leaderUserId)->select('privacy')->first();
        // if($anonymous==true){
        //     $anonymous = ($user->privacy === 1) ? true : false;
        // }
        $party=Party::where('id',$partyId)->first();      
        $postModel =  PostByParty::class;
        $postModelMeta = PostByPartyMeta::class;
        $postDetails = [
            "authorType" => $userType,
            "partyId" =>  $party->id,
            "postType" => "Complaint",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            "isPublished" =>$isPublished,
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
            "postByPartyId" => $post->id,
            'postDescriptions' => $description,
            "complaintLocation" =>$location,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByLeaderMeta = $postModelMeta::create($postByLeaderMetaData);
        $i = 1;
        if($media!=''){
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByLeaderMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }
        // return "Post Created Successfully";
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];

    }

    public static function createMultimediaPost($partyId, $postDetails, $media, $userType)
    {

        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $anonymous = $postDetails['anonymous'];
        $hashTagArray = strpos($hashTags, ',') !== false ? explode(',', $hashTags) : [$hashTags];
        $leaderUserId = Auth::user()->id;
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

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

        $party=Party::where('id',$partyId)->first();      
        $postModel =  PostByParty::class;
        $postModelMeta = PostByPartyMeta::class;

        $postDetails = [
            "authorType" => $userType,
            "partyId" => $party->id,
            "postType" => "MultiMedia",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            "isPublished" =>  $isPublished,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'sentiment' => $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByPartyMetaData = [
            "postByPartyId" => $post->id,
            'postDescriptions' => $description,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByPartyMeta = $postModelMeta::create($postByPartyMetaData);
        $i = 1;
        if($media!=''){
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByPartyMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }
        // return "Post Created Successfully";
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];
    }

    public static function createEventPost($partyId, $postDetails, $media, $userType)
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
        $leaderUserId = Auth::user()->id;
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

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

       
        $party=Party::where('id',$partyId)->first();      
        $postModel =  PostByParty::class;
        $postModelMeta = PostByPartyMeta::class;
        
        $postDetails = [
            "authorType" => $userType,
            "partyId" => $party->id,
            "postType" => "Events",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'isPublished' =>$isPublished,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'sentiment' => $sentiment,
            'abusivetext' => $abusivetext,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByPartyMetaData = [
            "postByPartyId" => $post->id,
            'postDescriptions' => $description,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByPartyMeta = $postModelMeta::create($postByPartyMetaData);
        $i = 1;
        if($media!=''){
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByPartyMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }
        $eventsDetails = [
            "postByPartyId" => $post->id,
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
        EventsByParty::create($eventsDetails);

        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];
        // return "Events Created Successfully";
    }
    public static function createIdeaPost($partyId, $postDetails, $media, $userType)
    {
        $title = $postDetails['title'];
        $description = $postDetails['description'];
        $hashTags = $postDetails['hashTags'];
        $mention = $postDetails['mention'];
        $department=$postDetails['department'];
        $anonymous = $postDetails['anonymous'];
        $leaderUserId = Auth::user()->id;
        $isPublished = $postDetails['isPublished'];
        $sentiment = $postDetails['sentiment'];
        $abusivetext = $postDetails['abusivetext'];
        $political = $postDetails['political'];
        $abusiveimage = $postDetails['abusiveimage'];

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

        
        $postModel =  PostByParty::class;
        $postModelMeta = PostByPartyMeta::class;
        $party=Party::where('id',$partyId)->first();
        $postDetails = [
            "authorType" => $userType,
            "partyId" => $party->id,
            "postType" => "Idea",
            "postTitle" => $title,
            "likesCount" => 0,
            "commentsCount" => 0,
            "shareCount" => 0,
            "anonymous" => $anonymous,
            "hashTags" => $hashTags,
            "mention" => $mention,
            'isPublished' =>$isPublished,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'abusivetext' => $abusivetext,
            'sentiment' => $sentiment,
            'political' => $political,
            'abusiveimage' => $abusiveimage
        ];
        $post = $postModel::create($postDetails);
        $lastInsertedId = $post->id;
        $postByPartyMetaData = [
            "postByPartyId" => $post->id,
            'postDescriptions' => $description,
            "ideaDepartment" =>$department,
            'createdBy' => $leaderUserId,
            'updatedBy' => $leaderUserId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ];

        $postByPartyMeta = $postModelMeta::create($postByPartyMetaData);
        $i = 1;
        if($media!=''){
        foreach ($media as $medias) {
            $postModelMeta::where('id', $postByPartyMeta->id)->update(['imageUrl' . $i => $medias['image']]);
            $i++;
        }
    }
        // return "Post Created Successfully";
        $response = ($isPublished == true) 
        ? "Post Created Successfully"
        : "Post to be verified";

        // return $response;
        return ['response' => $response, 'lastInsertedId' => $lastInsertedId];

    }
    public static function createStoryPost($partyId,$userType,$storyContent,$storytext)
    {
        $storyModel = StoryByParty::class;
        $leaderUserId = Auth::user()->id;
        $party=Party::where('id',$partyId)->first();
        $storyData=[
             "partyId" =>$party->id,
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