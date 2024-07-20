<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\Action;
use App\Helpers\HttpHelper;
use App\Models\Party;
use App\Models\User;
use App\Helpers\LogActivity;
use App\Helpers\PostByParty;
use App\Helpers\PostByPartyMangement;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostManagementByPartyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    /**
     * @OA\Post(
     *     path="/api/postByParty",
     *     summary="Add new Post By Party",
     *     tags={"Post By Party"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"postType", "title", "media", "polls"},
     *              @OA\Property(property="partyId", type="string", example="partyId"),
     *             @OA\Property(property="postType", type="string", example="postType"),
     *             @OA\Property(property="title", type="string", example="title"),
     *             @OA\Property(property="description", type="string", example="description"),
     *             @OA\Property(property="locationCordinates", type="string", example="locationCordinates"),
     *             @OA\Property(property="department", type="string", example="department"),
     *             @OA\Property(property="anonymous", type="string", example="true/false"),
     *             @OA\Property(property="mention", type="string", example="mention"),
     *             @OA\Property(property="hashTags", type="string", example="hashTags"),
     *             @OA\Property(property="pollEndDate", type="string", example="pollEndDate"),
     *             @OA\Property(property="pollEndTime", type="string", example="pollEndTime"),
     *             @OA\Property(property="eventStartDate", type="string", example="eventStartDate"),
     *            @OA\Property(property="eventEndDate", type="string", example="eventEndDate"),
     *             @OA\Property(property="eventStartTime", type="string", example="eventStartTime"),
     *            @OA\Property(property="eventEndTime", type="string", example="eventEndTime"),
     *             @OA\Property(
     *                 property="media",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"image"},
     *                     @OA\Property(property="image", type="string", format="image", example="/path/to/image1.jpg"),
     *                 ),
     *                 example={
     *                     {
     *                         "image": "/path/to/image1.jpg"
     *                     },
     *                     {
     *                         "image": "/path/to/image2.jpg"
     *                     }
     *                 },
     *                 description="Array of media objects"
     *             ),
     *             @OA\Property(
     *                 property="polls",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"option"},
     *                     @OA\Property(property="option", type="string", example="Option 1"),
     *                 ),
     *                 example={
     *                     {
     *                         "option": "Option 1"
     *                     },
     *                     {
     *                         "option": "Option 2"
     *                     }
     *                 },
     *                 description="Array of poll options"
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function store(Request $request)
    {
        try {
            $partyPostedId = $request->partyId;
            $userType = "Party";
            $postType = $request->postType;
            $title = $request->title;
            $description = $request->description;
            $hashTags = $request->hashTags;
            $mention = $request->mention;
            $media = $request->media ?? [];
            $location = $request->locationCordinates;
            $department = $request->department;
            $polls = $request->polls;
            $pollEndDate = $request->pollEndDate;
            $pollEndTime = $request->pollEndTime;
            $eventStartDate = $request->eventStartDate;
            $eventEndDate = $request->eventEndDate;
            $eventStartTime = $request->eventStartTime;
            $eventEndTime = $request->eventEndTime;
            $anonymous = $request->anonymous;
            $abusiveText = "non_abusive";
            $politicalText = "politics";
            $abusiveimageText = "non_vulgar";
            $sentimentText = HttpHelper::checkSentiment($description);
            $sentimentText = $sentimentText->result;
            $sentimentText = str_replace('"', '', $sentimentText);
            // $sentimentText = null;
            $postDetails = [
                'title' => $title,
                'description' => $description,
                'hashTags' => $hashTags,
                'mention' => $mention,
                'location' => $location,
                'department' => $department,
                'polls' => $polls,
                'pollEndDate' => $pollEndDate,
                'pollEndTime' => $pollEndTime,
                'eventStartDate' => $eventStartDate,
                'eventEndDate' => $eventEndDate,
                'eventStartTime' => $eventStartTime,
                'eventEndTime' => $eventEndTime,
                'anonymous' => $anonymous,
                'isPublished' => true,
                'abusivetext' => $abusiveText,
                'political' => $politicalText,
                'abusiveimage' => $abusiveimageText,
                'sentiment' =>  $sentimentText
            ];
            $pollscheck = collect($polls);
            $polloptions = $pollscheck->pluck('option')->implode(', ');
            $selectedDetails = [
                $postDetails['title'],
                $postDetails['description'],
                $postDetails['hashTags'],
                $postDetails['location'],
                $polloptions
            ];
            try {
                $verifyText = HttpHelper::checkText(implode(', ', array_filter($selectedDetails, 'is_string')));
                // $verifyPoliticalContent = HttpHelper::checkPoliticalNonPolitical(implode(', ', array_filter($selectedDetails, 'is_string')));
                $contentForPolitics = $title.", ".$description;
                $verifyPoliticalContent = HttpHelper::checkPoliticalNonPolitical($contentForPolitics);
                $verifyText = $verifyText->result;
                $verifyPoliticalContent = $verifyPoliticalContent->result;
                // $verifyText = "non_abusive";
                // $verifyPoliticalContent = "politics";
                if($verifyPoliticalContent == "non politics"){
                    $postDetails['isPublished'] = false;
                    $abusiveText = implode(', ', array_filter($postDetails, 'is_string'));
                    $postDetails['political'] = "non politics";
                }else{
                    if($verifyText == 'abusive'){
                        $postDetails['isPublished'] = false;
                        $abusiveText = implode(', ', array_filter($postDetails, 'is_string'));
                        $postDetails['abusivetext'] = "abusive";
                    }
                }
            } catch (\Exception $e) {

            }

            try {
                if($verifyText == "non_abusive" && $verifyPoliticalContent == "politics")
                {
                    $media = collect($media);
                    $verificationResults = []; // Array to store verification results
                    foreach ($media as $medias) {
                        $image = $medias['image'];
                        $verifyImage = HttpHelper::checkImage($image);
                        $verificationResults[] = $verifyImage;
                        if ($verifyImage == "vulgar") {
                            $postDetails['abusiveimage'] = "vulgar";
                            $postDetails['isPublished'] = false;
                            
                        }
                    }
                    $verificationResults = array_map(function ($item) {
                        return str_replace('"', '', $item);
                    }, $verificationResults);
                    if (in_array("vulgar", $verificationResults)) {
                        $postDetails['abusiveimage'] = "vulgar";
                        $postDetails['isPublished'] = false;
                    }
                }
            } catch (\Exception $e) {
                // Handle any exceptions that occur during the process
            }
            

         if ($mention != '') {
                $mentionArray = strpos($mention, ',') !== false ? explode(',', $mention) : [$mention];
                foreach ($mentionArray as $mention) {
                    $user = User::where('userName', $mention)->first();

                    if ($user) {
                        $partyId = ($user->leaderDetails && $user->leaderDetails->getLeaderCoreParty) ? $user->leaderDetails->getLeaderCoreParty->corePartyId : null;
                        if ($partyId != '') {
                            $party = Party::find($partyId)->nameAbbrevation;
                            $firstName = $user->firstName;
                            $lastName = $user->lastName;

                            $fullName = $firstName . ' ' . $lastName;

                            $fullName = $fullName . '-' . $party;
                        } else {
                            $firstName = $user->firstName;
                            $lastName = $user->lastName;

                            $fullName = $firstName . ' ' . $lastName;

                        }
                    } else {
                        $fullName = $mention;
                    }
                    $resultArray[] = $fullName;
                }
                $commaSeparatedString = implode(', ', $resultArray);
                $postDetails['mention'] = $commaSeparatedString;
     
            }
            // return "Send hello 2". $postType;

            $status = '';
            switch ($postType) {
                case 'Multimedia':
                    $status = PostByPartyMangement::createMultimediaPost($partyPostedId, $postDetails, $media, $userType);
                    break;
                case 'Complaint':
                    $status = PostByPartyMangement::createComplaintPost($partyPostedId, $postDetails, $media, $userType);
                    break;
                case 'Idea':
                    $status = PostByPartyMangement::createIdeaPost($partyPostedId, $postDetails, $media, $userType);
                    break;
                case 'Polls':
                    $status = PostByPartyMangement::createPollPost($partyPostedId, $postDetails, $media, $userType);
                    break;
                case 'Events':
                    $status = PostByPartyMangement::createEventPost($partyPostedId, $postDetails, $media, $userType);
                    break;
                default:
                    $status = 'Something Went Wrong';
            }
            // $response = ($status === "Post to be verified") 
            //                 ? response()->json(['status' => 'Failed', 'message' => $status], 202)
            //                 : response()->json(['status' => 'success', 'message' => $status], 200);

            //             return $response;
            if ($status['response'] === 'Post Created Successfully') {
                $lastInsertedId = $status['lastInsertedId'];
                $postByPartyName = Party::find($partyPostedId)->name;
                if ($user = User::where('userName', $mention)->first()) {
                    $userId = $user->userDetails->userId;
                    $leaderId = $user->id;
                    $replaceArray = ['{name}' => $postByPartyName];
                    $notificationType = 'mention';
                    $userType = 'Leader';
                    $replaceArray = ['{name}' => $anonymous ? 'Anonymous' : $postByPartyName];
                    $userId = $anonymous ? null : $userId;
                    $getNotification = Action::getNotification('userLeader', $notificationType);
                    $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                    Action::createNotification($userId, $userType, $leaderId,$message,"Post",$lastInsertedId, "Party" );
                } elseif ($party = Party::where('name', $mention)->first()) {
                    $partyId = $party->id;
                    $userId = $party->id;
                    $replaceArray = ['{name}' => $postByPartyName, '{partyName}' => $party->name];
                    $notificationType = 'mention';
                    $userType = 'Party';
                    $getNotification = Action::getNotification('party', $notificationType);
                    $message = str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification);
                    Action::createNotification($userId, $userType, $partyId,$message,"Post",$lastInsertedId,"Party" );
                }
                return response()->json(['status' => 'success', 'message' => $status['response'], 'lastInsertedId' => $lastInsertedId], 200);
            }else {
                return response()->json(['status' => 'Failed', 'message' => $status['response']], 202);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/createStoryByParty",
     *     summary="Create New Story By Party",
     *     tags={"Post By Party"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"storyContent","partyId"},
     *         @OA\Property(property="storyContent", type="string", example="image/video/url"),
     *        @OA\Property(property="storytext", type="string", example="text"),
     *        @OA\Property(property="partyId", type="string", example="partyId")
     *         
     *      ),
     *   ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *           mediaType="application/json",
     *         )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not found "
     *   ),
     *   security={{ "apiAuth": {} }}
     *)
     **/
    public function createStoryByParty(Request $request)
    {
        try {
        $userType = 'Party';
        $partyId = $request->partyId;
        $storyContent = $request->storyContent;
        $storytext = $request->storytext;
        $checkImageOrNot = HttpHelper::checkImageExtensions($storyContent);
            if($storytext == ''){
                if($checkImageOrNot == 1){
                    $verifyImage = HttpHelper::checkImage($storyContent);
                    $verifyImage = str_replace('"', '', $verifyImage);
                    $verifyText = "non_abusive";
                }else {
                    $verifyText = HttpHelper::checkText($storyContent);
                    $verifyText = $verifyText->result;
                    $verifyImage = "non_vulgar";
                }
            }else {
                $verifyText = HttpHelper::checkText($storytext);
                $verifyText = $verifyText->result;
    
                $verifyImage = HttpHelper::checkImage($storyContent);
                $verifyImage = str_replace('"', '', $verifyImage);
            }
        if($verifyText == "abusive" || $verifyImage == "vulgar"){
            return response()->json(['status' => 'error', 'message' => "System detected content was in-appropriate and can not be allowed."], 404);
        }
        $status = PostByPartyMangement::createStoryPost($partyId, $userType, $storyContent, $storytext);
        return response()->json(['status' => 'success', 'message' => $status], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}