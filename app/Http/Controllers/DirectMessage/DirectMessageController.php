<?php

namespace App\Http\Controllers\DirectMessage;
use DB;
use Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\PartyFollowers;
use Illuminate\Support\Carbon;
use App\Models\LeaderFollowers;
use App\Models\DirectMessageRequest;
use App\Models\DirectMessage;
use App\Helpers\LogActivity;
use App\Helpers\Action;
use App\Models\User;

class DirectMessageController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/leaderPartyYouFollow",
     *     summary="leaderPartyYouFollow",
     *     tags={"Direct Message"},
     *    @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthorized "
     *   ),
     *    @OA\Response(
     *      response=404,
     *       description="Error / Data not foun "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function leaderPartyYouFollow(Request $request)
    {
        try{
            // $perPage = env('PAGINATION_PER_PAGE', 10);
        $perPage = 10;
        $filterKeyword = $request->keyword;
        $leaderFollowers = LeaderFollowers::where('followerId', Auth::user()->id)
            ->join('users', 'leader_followers.leaderId', '=', 'users.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->select(
                'leader_followers.id as leaderFollowingId',
                'users.id as leaderId',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->where(function ($query) use ($filterKeyword) {
                $query->where('users.firstName', 'ILIKE', "$filterKeyword%")
                      ->orWhere('users.firstName', 'ILIKE', "%$filterKeyword%")
                      ->orWhere('users.lastName', 'ILIKE', "$filterKeyword%")
                      ->orWhere('users.lastName', 'ILIKE', "%$filterKeyword%");
            })
            ->orderByRaw("CASE WHEN \"users\".\"firstName\" ILIKE '$filterKeyword%' THEN 0 ELSE 1 END, 
                            CASE WHEN \"users\".\"lastName\" ILIKE '$filterKeyword%' THEN 0 ELSE 1 END,
                            \"users\".\"firstName\" ILIKE '%$filterKeyword%' DESC, 
                            \"users\".\"lastName\" ILIKE '%$filterKeyword%' DESC")
            ->orderBy('leader_followers.createdAt', 'desc')
            ->paginate($perPage);
            $currentPage = $leaderFollowers->currentPage();
            $lastPage = $leaderFollowers->lastPage();

            $mappedLeaders = $leaderFollowers->map(function ($follower) use ($currentPage){
                $follower['currentPage'] = $currentPage;
                return $follower;
            });

            $partyFollowers = PartyFollowers::where('followerId', Auth::user()->id)
                ->with(['party:id,name,logo,nameAbbrevation'])
                ->whereHas('party', function ($query) use ($filterKeyword) {
                    $query->where('parties.name', 'ILIKE', "$filterKeyword%")
                        ->orWhere('parties.name', 'ILIKE', "%$filterKeyword%");
                })
            //     ->orderByRaw("CASE WHEN \"parties\".\"name\" ILIKE '$filterKeyword%' THEN 0
            //    WHEN \"parties\".\"name\" ILIKE '%$filterKeyword%' THEN 1
            //    ELSE 2 END")
                ->orderBy('party_followers.createdAt', 'desc')
                ->paginate($perPage);

                $currentPage = $partyFollowers->currentPage();
                $lastPage = $partyFollowers->lastPage();

                $mappedParties = $partyFollowers->map(function ($follower) use ($currentPage) {
                    $stateCode = !empty($follower->party->getStateCode()) ? $follower->party->getStateCode() : '';
                    return [
                        'followingId' => $follower->id,
                        'partyId' => $follower->party->id,
                        'name' => $follower->party->name,
                        'nameAbbrevation' => $follower->party->nameAbbrevation,
                        'logo' => $follower->party->logo,
                        'stateCode' => $stateCode,
                        'currentPage' => $currentPage,
                    ];
                });
            
        return response()->json(['status' => 'success', 'leader' => $mappedLeaders, 'mappedParties' => $mappedParties,'current_page' => $currentPage,
        'last_page' => $lastPage], 200);
        }catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
        
    }

    /**
     * @OA\Post(
     *     path="/api/sendMessage",
     *     summary="Send Message",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"senderId", "senderType", "receiverId", "receiverType", "message"},
     *             @OA\Property(property="senderId", type="uuid", description="ID of the sender", example=""),
     *             @OA\Property(property="senderType", type="string", description="Type of the sender", example="Leader/Party/Citizen"),
     *             @OA\Property(property="receiverId", type="uuid", description="ID of the receiver", example=""),
     *             @OA\Property(property="receiverType", type="string", description="Type of the receiver", example="Leader/Party/Citizen"),
     *             @OA\Property(property="message", type="string", description="Content of the message"),
     *              @OA\Property(
    *                 property="media",
    *                 type="array",
    *                 @OA\Items(
    *                     type="string",
    *                     example="/path/to/image1.jpg",
    *                 ),
    *                 description="Array of media URLs"
    *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function sendMessage(Request $request)
    {
        try{
            $request->validate([
                'senderId' => 'required',
                'senderType' => 'required',
                'receiverId' => 'required',
                'receiverType' => 'required',
                'message' => 'required|string',
            ]);
            $senderId = $request->senderId;
            $senderType = $request->senderType;
            $receiverId = $request->receiverId;
            $receiverType = $request->receiverType;
            $message = $request->message;
            $media = $request->media;
            if($senderType == "Leader" || $senderType == "Party"){
                $existingRequest = DirectMessageRequest::where('senderId', $receiverId)
                                                        ->where('senderType', $receiverType)
                                                        ->where('receiverId', $senderId)
                                                        ->where('receiverType', $senderType)
                                                        ->orderBy('created_at', 'desc')
                                                       ->first();
                if(isset($existingRequest)){
                    if(($existingRequest->status != "Accepted")){
                        return response()->json(['status' => 'error', 'message' => 'you have to accept request'], 400);  
                    } else {
                            $createMessage = [
                                'senderId' => $senderId,
                                'senderType' => $senderType,
                                'receiverId' => $receiverId,
                                'receiverType' => $receiverType,
                                'message' => $message,
                                'media' => json_encode($media),
                            ];
                            $newMessage = DirectMessage::create($createMessage);
                            return response()->json(['status' => 'success', 'message' => 'Message sent successfully','messageId' => $newMessage->id ], 200); 
                    }
                }else {
                    return response()->json(['status' => 'error', 'message' => 'you have to accept request'], 400);  
                }
                
            }else {
                $existingRequest = DirectMessageRequest::where('senderId', $senderId)
                                                        ->where('senderType', $senderType)
                                                        ->where('receiverId', $receiverId)
                                                        ->where('receiverType', $receiverType)
                                                        ->orderBy('created_at', 'desc')
                                                        ->first();
                if($existingRequest){
                    $status = $existingRequest->status;
                    switch ($status) {
                        case 'Pending':
                            return $this->handlePendingRequest($existingRequest,$message, $media);
                            break;
                        case 'Accepted':
                            return $this->handleAcceptedRequest($existingRequest, $message, $media);
                            break;
                        case 'Declined':
                            return response()->json(['status' => 'error', 'message' => 'Your request has been declined'], 400);
                            break;
                        default:
                            // Handle unexpected status
                            return response()->json(['status' => 'error', 'message' => 'Unexpected request status'], 400);
                            break;
                    }
                } else {
                    //not present
                    $lastInsertedId = $this->createMessageRequest($senderId, $senderType, $receiverId, $receiverType, $message, $media);
                    return response()->json(['status' => 'success', 'message' => 'Message sent successfully','messageId' => $lastInsertedId], 200);
                }
            }
            
            
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
        
    }
    public function createMessageRequest($senderId, $senderType, $receiverId, $receiverType, $message, $media)
    {
        $createMessage = [
            'senderId' => $senderId,
            'senderType' => $senderType,
            'receiverId' => $receiverId,
            'receiverType' => $receiverType,
            'message' => $message,
            'media' => json_encode($media),
            'status' => "Pending",
        ];
        DirectMessageRequest::create($createMessage);
        $newMessage = DirectMessage::create($createMessage);
        $fetchUserdetails = User::select('firstName', 'lastName')->find($senderId);
        $name = $fetchUserdetails->firstName." ".$fetchUserdetails->lastName;
        $notificationmessage = $name." wants to send you a message";
        Action::createNotification($senderId,$receiverType,$receiverId,$notificationmessage,"DirectMessage",$senderId,$senderType);
        return $newMessage->id;
    }
    public function handlePendingRequest($existingRequest,$message,$media)
    {
        // Get the count of pending requests for the sender and receiver
        $count = DirectMessageRequest::where('senderId', $existingRequest->senderId)
        ->where('senderType', $existingRequest->senderType)
        ->where('receiverId', $existingRequest->receiverId)
        ->where('receiverType', $existingRequest->receiverType)
        ->where('status', 'Pending')
        ->count();
        if ($count >= 3) {
            return response()->json(['status' => 'error', 'message' => 'All requests have been sent.'], 400);
        }
        $latestRequest = DirectMessageRequest::where('senderId', $existingRequest->senderId)
        ->where('senderType', $existingRequest->senderType)
        ->where('receiverId', $existingRequest->receiverId)
        ->where('receiverType', $existingRequest->receiverType)
        ->where('status', 'Pending')
        ->orderBy('created_at', 'desc')
        ->first();
        // Calculate the time elapsed since the latest request
        $currentTime = now();
        $latestRequestTime = $latestRequest->created_at;
        $timeDifference = $currentTime->diffInHours($latestRequestTime);
        // If the time difference is less than 1 hour, show a message to wait
        // if ($timeDifference < 1) {
        //     return response()->json(['status' => 'error', 'message' => 'You have already sent a request. Please wait for an hour before sending another.'], 400);
        // }
        $storeMessage = [
            'senderId' => $existingRequest->senderId,
            'senderType' => $existingRequest->senderType,
            'receiverId' => $existingRequest->receiverId,
            'receiverType' => $existingRequest->receiverType,
            'media' => json_encode($media),
            'status' => 'Pending',
            'message' => $message, // Or use the new message if required
        ];
        DirectMessageRequest::create($storeMessage);
        $newMessage = DirectMessage::create($storeMessage);
        return response()->json(['status' => 'success', 'message' => 'Message sent successfully', 'messageId' => $newMessage->id], 200);
    }
    public function handleAcceptedRequest($existingRequest, $message, $media)
    {
        // Store the message since the request has been accepted
        // Example code:
        $storeMessage = [
            'senderId' => $existingRequest->senderId,
            'senderType' => $existingRequest->senderType,
            'receiverId' => $existingRequest->receiverId,
            'receiverType' => $existingRequest->receiverType,
            'message' => $message,
            'media' => json_encode($media),
        ];
        $newMessage = DirectMessage::create($storeMessage);
        return response()->json(['status' => 'success', 'message' => 'Message sent successfully', 'messageId' => $newMessage->id], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/requestListDm",
     *     summary="Request List DM",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"Id", "Type", "status"},
     *             @OA\Property(property="Id", type="uuid", description="ID of the sender", example="LeaderId/PartyId"),
     *             @OA\Property(property="Type", type="string", description="Type of the sender",example="Leader/Party"),
     *             @OA\Property(property="status", type="string", description="Acceptep/Pending/Declined")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function requestListDm(Request $request)
    {
        try{
            
            $Id = $request->Id;
            $Type = $request->Type;
            $status = $request->status;

            if($Type == "Citizen"){
                $receiverId = 'senderId';
                $receiverType = 'senderType';
            }else {
                $receiverId = 'receiverId';
                $receiverType = 'receiverType';
            }
            
            $perPage = 10;
            $existingRequest = DirectMessageRequest::select('direct_message_requests.id',
                                                            'direct_message_requests.senderId',
                                                            'direct_message_requests.senderType',
                                                            'direct_message_requests.receiverId',
                                                            'direct_message_requests.receiverType',
                                                            'direct_message_requests.message',
                                                            'direct_message_requests.status',
                                                            'direct_message_requests.created_at',
                                                            'users.firstName',
                                                            'users.lastName',
                                                            'user_details.profileImage',
                                                            )
                                                        ->where($receiverId, $Id)
                                                        ->where($receiverType, $Type)
                                                        ->when($status == 'Pending', function ($query) {
                                                            $query->whereIn('direct_message_requests.status', ['Pending', 'Declined']);
                                                        }, function ($query) use ($status) {
                                                            $query->where('direct_message_requests.status', $status);
                                                        })
                                                        ->join('users', 'direct_message_requests.senderId', '=', 'users.id')
                                                        ->join('user_details', 'users.id', '=', 'user_details.userId')
                                                        ->orderBy('senderId')
                                                        ->orderBy('created_at', 'desc')
                                                        ->distinct('senderId')
                                                        ->paginate($perPage);
                $currentPage = $existingRequest->currentPage();
                $lastPage = $existingRequest->lastPage();
                $existingRequest->each(function ($item) use ($currentPage) {
                    $item->currentPage = $currentPage;
                    $unreadCount = DirectMessage::where('receiverId', $item->receiverId)
                        ->where('senderId', $item->senderId)
                        ->where('is_read', false)
                        ->count();
                    $item->unread_count = $unreadCount;
                    // Retrieve latest message
                    $latestMessage = DirectMessage::where(function ($query) use ($item) {
                        $query->where('receiverId', $item->receiverId)
                            ->where('receiverType', $item->receiverType)
                            ->where('senderId', $item->senderId)
                            ->where('senderType', $item->senderType);
                    })
                    ->orWhere(function ($query) use ($item) {
                        $query->where('receiverId', $item->senderId)
                                ->where('receiverType',$item->senderType)
                            ->where('senderId', $item->receiverId)
                            ->where('senderType', $item->receiverType);
                    })
                    ->orderBy('created_at', 'desc') // Order by created_at in descending order
                    ->first(); // Select the first result
                    if ($latestMessage && $latestMessage->archieve) {
                        $item->latest_message = 'Message deleted';
                        $item->latest_message_time = $latestMessage ? $latestMessage->created_at->format('Y-m-d H:i:s') : null;
                    } else {
                        $item->latest_message = $latestMessage ? $latestMessage->message : null;
                        $item->latest_message_time = $latestMessage ? $latestMessage->created_at->format('Y-m-d H:i:s') : null;
                    }
                });
                $sortedRequests = $existingRequest->sortByDesc(function ($item) {
                    return $item->latest_message_time;
                });
            return response()->json([
                'status' => 'success',
                'message' => "list of request",
                'existingRequest' => $sortedRequests->values()->all(), // Only pagination information
                'current_page' => $currentPage,
                'last_page' => $lastPage
            ], 200);
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
        
    }


    /**
     * @OA\Post(
     *     path="/api/requestListCitizenDm",
     *     summary="Request List Citizen DM",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"Id", "Type", "status"},
     *             @OA\Property(property="Id", type="uuid", description="ID of the sender", example="LeaderId/PartyId"),
     *             @OA\Property(property="Type", type="string", description="Type of the sender",example="Leader/Party"),
     *             @OA\Property(property="status", type="string", description="Acceptep/Pending/Declined")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function requestListCitizenDm(Request $request)
    {
        try {
            $Id = $request->Id;
            $Type = $request->Type;
            $status = $request->status;
            $receiverId = 'senderId';
            $receiverType = 'senderType';
            $perPage = 10;

            $existingRequest = DirectMessageRequest::select(
                'direct_message_requests.id',
                'direct_message_requests.senderId',
                'direct_message_requests.senderType',
                'direct_message_requests.receiverId',
                'direct_message_requests.receiverType',
                'direct_message_requests.message',
                'direct_message_requests.status',
                'direct_message_requests.created_at',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->where($receiverId, $Id)
            ->where($receiverType, $Type)
            ->when($status == 'Pending', function ($query) {
                $query->whereIn('direct_message_requests.status', ['Pending', 'Declined']);
            }, function ($query) use ($status) {
                $query->where('direct_message_requests.status', $status);
            })
            ->join('users', 'direct_message_requests.receiverId', '=', 'users.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->orderBy('receiverId')
            ->orderBy('created_at', 'desc')
            ->distinct('receiverId')
            ->paginate($perPage);

            $currentPage = $existingRequest->currentPage();
            $lastPage = $existingRequest->lastPage();

            // Add unread_count and latest_message for each request
            $existingRequest->each(function ($item) use ($currentPage) {
                $item->currentPage = $currentPage;
                // Calculate unread_count
                $unreadCount = DirectMessage::where('receiverId', $item->senderId)
                    ->where('senderId', $item->receiverId)
                    ->where('is_read', false)
                    ->count();
                $item->unread_count = $unreadCount;
                // Retrieve latest message
                $latestMessage = DirectMessage::where(function ($query) use ($item) {
                    $query->where('receiverId', $item->senderId)
                            ->where('receiverType', $item->senderType)
                        ->where('senderId', $item->receiverId)
                        ->where('senderType', $item->receiverType);
                })
                ->orWhere(function ($query) use ($item) {
                    $query->where('receiverId', $item->receiverId)
                    ->where('receiverType', $item->receiverType)
                        ->where('senderId', $item->senderId)
                        ->where('senderType', $item->senderType);
                })
                ->orderBy('created_at', 'desc') // Order by created_at in descending order
                ->first(); // Select the first result
                if ($latestMessage && $latestMessage->archieve) {
                    $item->latest_message = 'Message deleted';
                    $item->latest_message_time = $latestMessage ? $latestMessage->created_at->format('Y-m-d H:i:s') : null;
                } else {
                    $item->latest_message = $latestMessage ? $latestMessage->message : null;
                    $item->latest_message_time = $latestMessage ? $latestMessage->created_at->format('Y-m-d H:i:s') : null;
                }
            });
            $sortedRequests = $existingRequest->sortByDesc(function ($item) {
                return $item->latest_message_time;
            });
            return response()->json([
                'status' => 'success',
                'message' => "list of request",
                'existingRequest' => $sortedRequests->values()->all(),
                'current_page' => $currentPage,
                'last_page' => $lastPage
            ], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/changeStatus",
     *     summary="Change Status",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"messageId", "status"},
     *             @OA\Property(property="messageId", type="uuid", description="ID of the message", example=""),
     *             @OA\Property(property="status", type="string", description="Content of the message", example="Accepted/Declined")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function changeStatus(Request $request)
    {
        try{
            $request->validate([
                'messageId' => 'required',
                'status' => 'required|string',
            ]);
            $messageId = $request->messageId;
            $status = $request->status;
            $message = DirectMessageRequest::find($messageId);
            if (!$message) {
                return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
            }
            $receiverId = $message->receiverId;
            $receiverType = $message->receiverType;
            $senderId = $message->senderId;
            $senderType = $message->senderType;
            $message->status = $status;
            $message->save();
            // $checkmessage = $message->message;
            // $takeIdFromDirectMessage = DirectMessage::where('receiverId', $receiverId)
            //                                             ->where('receiverType',$receiverType )
            //                                             ->where('senderId',$senderId )
            //                                             ->where('senderType',$senderType )
            //                                             ->where('message',$checkmessage)
            //                                             ->orderBy('created_at', 'desc')
            //                                             ->first();
            // $directMessageID = $takeIdFromDirectMessage->id;
            // $takeIdFromDirectMessage->status = $status;
            // $takeIdFromDirectMessage->save();

            DirectMessage::where('receiverId', $receiverId)
            ->where('receiverType', $receiverType)
            ->where('senderId', $senderId)
            ->where('senderType', $senderType)
            ->update(['status' => $status]);

            $updateRemainingRows = DirectMessageRequest::where('receiverId', $receiverId)
                                                            ->where('receiverType',$receiverType )
                                                            ->where('senderId',$senderId )
                                                            ->where('senderType',$senderType );
            $updateRemainingRows->update(['status' => $status]);
            $fetchUserdetails = User::select('firstName', 'lastName')->find($receiverId);
            $name = $fetchUserdetails->firstName." ".$fetchUserdetails->lastName;
            $notificationmessage = $name." has ".$status." your request DM.";
            Action::createNotification($receiverId,$senderType,$senderId,$notificationmessage,"DirectMessage",$receiverId,$receiverType);
            return response()->json(['status' => 'success', 'message' => 'Status changed successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
        
    }

    /**
     * @OA\Post(
     *     path="/api/displayMessage",
     *     summary="Display Message",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"senderId", "senderType", "receiverId", "receiverType"},
     *             @OA\Property(property="senderId", type="uuid", description="ID of the sender", example=""),
     *             @OA\Property(property="senderType", type="string", description="Type of the sender", example="Leader/Party/Citizen"),
     *             @OA\Property(property="receiverId", type="uuid", description="ID of the receiver", example=""),
     *             @OA\Property(property="receiverType", type="string", description="Type of the receiver", example="Leader/Party/Citizen")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */
    public function displayMessage(Request $request)
    {
        try{
            $receiverId = $request->receiverId;
            $receiverType = $request->receiverType;
            $senderId = $request->senderId;
            $senderType = $request->senderType;
            $perPage = 10;
            $conditions = [
                ['receiverId', $receiverId],
                ['receiverType', $receiverType],
                ['senderId', $senderId],
                ['senderType', $senderType],
            ];
            if($senderType != "Citizen"){
                $conditions = [
                    ['receiverId', $senderId],
                    ['receiverType', $senderType],
                    ['senderId', $receiverId],
                    ['senderType', $receiverType],
                ];
            }
            $rowCount = DirectMessageRequest::where($conditions)->count();
            $messageRemaining = 3 - $rowCount;
            $checkStatus = DirectMessageRequest::where($conditions)
                ->orderBy('created_at', 'desc')
                ->first();
            $messageStatus = $checkStatus ? $checkStatus->status : null;
            if($messageStatus == "Accepted"){
                $displaymessage = DirectMessage::where('receiverId', $receiverId)
                                    ->where('receiverType', $receiverType)
                                    ->where('senderId', $senderId)
                                    ->where('senderType', $senderType)
                                    ->orWhere(function($query) use ($senderId, $senderType, $receiverId, $receiverType) {
                                        $query->where('receiverId', $senderId)
                                            ->where('receiverType', $senderType)
                                            ->where('senderId', $receiverId)
                                            ->where('senderType', $receiverType);
                                    })
                                    ->join('users', 'direct_messages.senderId', '=', 'users.id')
                                    ->join('user_details', 'users.id', '=', 'user_details.userId')
                                    ->select(
                                        'direct_messages.id as id',
                                        'direct_messages.senderId as senderId',
                                        'direct_messages.senderType as senderType',
                                        'direct_messages.receiverId as receiverId',
                                        'direct_messages.receiverType as receiverType',
                                        'direct_messages.status as status',
                                        'direct_messages.is_read as is_read',
                                        'direct_messages.message as message',
                                        'direct_messages.media as media',
                                        'direct_messages.archieve as archieve',
                                        'direct_messages.created_at as created_at',
                                        'direct_messages.updated_at as updated_at',
                                        'users.firstName',
                                        'users.lastName',
                                        'user_details.profileImage'
                                    )
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($perPage);
                $currentPage = $displaymessage->currentPage();
                $lastPage = $displaymessage->lastPage();
                // return response()->json(['status' => 'success', 'message' => "list of messages", 'data' => $displaymessage], 200);
                $displaymessage->each(function ($item) use ($currentPage) {
                    $item->currentPage = $currentPage;
                    $item->media = json_decode($item->media);
                });

            }else {
                    $displaymessage = DirectMessageRequest::where('receiverId', $receiverId)
                                    ->where('receiverType', $receiverType)
                                    ->where('senderId', $senderId)
                                    ->where('senderType', $senderType)
                                    ->orWhere(function($query) use ($senderId, $senderType, $receiverId, $receiverType) {
                                        $query->where('receiverId', $senderId)
                                            ->where('receiverType', $senderType)
                                            ->where('senderId', $receiverId)
                                            ->where('senderType', $receiverType);
                                    })
                                    ->join('users', 'direct_message_requests.senderId', '=', 'users.id')
                                    ->join('user_details', 'users.id', '=', 'user_details.userId')
                                    ->select(
                                        'direct_message_requests.id as id',
                                        'direct_message_requests.senderId as senderId',
                                        'direct_message_requests.senderType as senderType',
                                        'direct_message_requests.receiverId as receiverId',
                                        'direct_message_requests.receiverType as receiverType',
                                        'direct_message_requests.status as status',
                                        'direct_message_requests.message as message',
                                        'direct_message_requests.media as media',
                                        'direct_message_requests.created_at as created_at',
                                        'direct_message_requests.updated_at as updated_at',
                                        'users.firstName',
                                        'users.lastName',
                                        'user_details.profileImage'
                                    )
                                    ->orderBy('created_at', 'asc')
                                    ->paginate($perPage);
                $currentPage = $displaymessage->currentPage();
                $lastPage = $displaymessage->lastPage();
                $remainingSendMessagesAvailable = 2;
                // return response()->json(['status' => 'success', 'message' => "list of messages", 'data' => $displaymessage], 200);
                $displaymessage->each(function ($item) use ($currentPage, &$remainingSendMessagesAvailable) {
                    $item->currentPage = $currentPage;
                    $item->media = json_decode($item->media);
                    $item->remainingSendMessagesAvailable = $remainingSendMessagesAvailable;
                    $remainingSendMessagesAvailable--; // Decrement the counter
                });
            }
            
            
            return response()->json([
                'status' => 'success',
                'message' => "list of messages",
                'messageStatus' => $messageStatus,
                'noMessageCount' => $rowCount,
                'messageRemaining' => $messageRemaining,
                'data' => $displaymessage->items(), // Paginated items
                'current_page' => $currentPage,
                'last_page' => $lastPage
            ], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/isReadDirectMessage",
     *     summary="Is Read Message",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"senderId", "senderType", "receiverId", "receiverType", "message"},
     *             @OA\Property(property="senderId", type="uuid", description="ID of the sender", example=""),
     *             @OA\Property(property="senderType", type="string", description="Type of the sender", example="Leader/Party/Citizen"),
     *             @OA\Property(property="receiverId", type="uuid", description="ID of the receiver", example=""),
     *             @OA\Property(property="receiverType", type="string", description="Type of the receiver", example="Leader/Party/Citizen")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */

     public function isReadDirectMessage(Request $request)
     {
        try{
            $receiverId = $request->receiverId;
            $receiverType = $request->receiverType;
            $senderId = $request->senderId;
            $senderType = $request->senderType;

            DirectMessage::where('receiverId', $receiverId)
            ->where('receiverType', $receiverType)
            ->where('senderId', $senderId)
            ->where('senderType', $senderType)
            ->update(['is_read' => true]);
            return response()->json(['status' => 'success', 'message' => "Direct Message Notifications marked as read"], 200); 
            
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
     }

     /**
     * @OA\Post(
     *     path="/api/isNotReadDirectMessage",
     *     summary="To check Not Read Messages",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"senderId", "senderType", "receiverId", "receiverType", "message"},
     *             @OA\Property(property="senderId", type="uuid", description="ID of the sender", example=""),
     *             @OA\Property(property="senderType", type="string", description="Type of the sender", example="Leader/Party/Citizen"),
     *             @OA\Property(property="receiverId", type="uuid", description="ID of the receiver", example=""),
     *             @OA\Property(property="receiverType", type="string", description="Type of the receiver", example="Leader/Party/Citizen")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized "
     *     ),
     * security={{ "apiAuth": {} }}
     * )
     */

     public function isNotReadDirectMessage(Request $request)
     {
        try{
            $receiverId = $request->receiverId;
            $receiverType = $request->receiverType;
            $senderId = $request->senderId;
            $senderType = $request->senderType;

            $unreadMessageCount = DirectMessage::where('receiverId',$receiverId)
                            ->where('receiverType',$receiverType)
                            ->where('senderId',$senderId)
                            ->where('senderType',$senderType)
                            ->where('is_read', 0)
                            ->whereNull('status')
                            ->count();
            return response()->json(['status' => 'success', 'message' => "Direct Message Notifications", 'unreadMessageCount' => $unreadMessageCount], 200);  
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
     }


     /**
     * @OA\Delete(
     *     path="/api/deleteMessage",
     *     summary="Delete Message",
     *     tags={"Direct Message"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Message details",
     *         @OA\JsonContent(
     *             required={"messageId"},
     *             @OA\Property(property="messageId", type="uuid", description="ID of the message to delete", example="")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Message not found"
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function deleteMessage(Request $request)
    {
        try {
            $request->validate([
                'messageId' => 'required|uuid',
            ]);
            $messageId = $request->messageId;
            $message = DirectMessage::find($messageId);
            if (!$message) {
                return response()->json(['status' => 'error', 'message' => 'Message not found'], 404);
            }
            $message->archieve = true;
            $message->save();
            // $message->delete();
            return response()->json(['status' => 'success', 'message' => 'Message deleted successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Server Error'], 500);
        }
    }

}
