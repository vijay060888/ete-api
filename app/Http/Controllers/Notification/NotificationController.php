<?php
 
namespace App\Http\Controllers\Notification;
 
use Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Party;
use App\Helpers\Action;
use App\Models\Broadcast;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\PartyFollowers;
use App\Models\LeaderFollowers;
use App\Http\Controllers\Controller;
 
class NotificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/notification",
     *     summary="Fetch all notification",
     *     tags={"Notification"},
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
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function index()
    {
        $partyId = request('partyId');
        $typeId = $partyId ? $partyId : Auth::user()->id;
 
        $partyFollowers = PartyFollowers::where('followerId', $typeId)->pluck('partyId');
        $leaderFollowers = LeaderFollowers::where('followerId', $typeId)->pluck('leaderId');

        // Get the current page number from the request
        $currentTodayPage = request()->query('page');
        // Get the current page number from the request for olderNotifications
        // $currentOlderPage = request()->query('older_page');
        Notification::where('typeId', $typeId)->update(['isRead' => true]);
        $todayNotification = Notification::where('typeId', $typeId)
            // ->whereDate('createdAt', Carbon::today())
            ->orderByRaw('CASE WHEN notifications.broadcast_id IS NOT NULL THEN 0 ELSE 1 END, "notifications"."createdAt" DESC')
            ->paginate(10, ['*'], 'page', $currentTodayPage);
        $todayNotifications = $todayNotification->map(function ($todayNotification) use ($leaderFollowers,$partyFollowers) {
            $formattedDate = $todayNotification->createdAt->diffForHumans();
            $profileImage = null;
 
            if ($todayNotification->userId) {
                $user = User::where('id', $todayNotification->userId)->with('userDetails')->first();
 
                if ($user) {
                    $profileImage = optional($user->userDetails)->profileImage;
                } else {
                    $party = Party::where('id', $todayNotification->userId)->first();
                    $profileImage = optional($party)->logo;
                }
            } else {
                $profileImage = $todayNotification->profileImage;
            }
            $isFollowing = null;
            if($todayNotification->userType == "Party"){
                $isFollowing = $partyFollowers->contains($todayNotification->userId);
            }else {
                $isFollowing = $leaderFollowers->contains($todayNotification->userId);
            }
            $userc = User::find($todayNotification->createdBy);
            $userType = $userc->getRoleNames()[0];
 

            $broadcast_id = null;
            $broadcast_name = null;
            $broadcast_broadcastTitle = null;
            $broadcast_message = null;
            $broadcast_image = null;
            $broadcast_url = null;
            $broadcast_hashtags = null;
            $broadcast_createdByType = null;
           
            $broadcast_createdBy = null;
            
            if ($todayNotification->broadcast_id) {
                $broadcast = Broadcast::find($todayNotification->broadcast_id);
                if ($broadcast && $broadcast->createdAt !== null && $broadcast->createdAt->diffInHours(now()) <= 24) {
                    $broadcast_id = $broadcast->id;
                    $broadcast_user = User::find($broadcast->createdBy);
                    $broadcast_name = $broadcast_user ? $broadcast_user->firstName. ' ' . $broadcast_user->lastName : null;
                    $broadcast_broadcastTitle = $broadcast->broadcastTitle;
                    $broadcast_message = $broadcast->broadcastMessage;
                    $broadcast_image = $broadcast->image;
                    $broadcast_url = $broadcast->url;
                    $broadcast_hashtags = $broadcast->hashtags;
                    $broadcast_createdByType = $broadcast->createdByType;
                    $broadcast_createdBy = $broadcast->createdBy;
                }
            }
           
 
            return [
                'id' => $todayNotification->id,
                'notificationIcon' => $profileImage,
                'isRead' => $todayNotification->isRead,
                'notificationMessage' => $todayNotification->notificationMessage,
                'type' => $todayNotification->type,
                'createdAt' => $formattedDate,
                'isFollowing' => $isFollowing,
                'createdByType' => $userType,
                'broadcast_id' => $broadcast_id,
                'broadcast_name' => $broadcast_name,
                'broadcast_title' => $broadcast_broadcastTitle,
                'broadcast_message' => $broadcast_message,
                'broadcast_image' => $broadcast_image,
                'broadcast_url' => $broadcast_url,
                'broadcast_hashtags' => $broadcast_hashtags,
                'broadcast_createdByType' => $broadcast_createdByType,
                'broadcast_createdBy' => $broadcast_createdBy,
                'notificationtype' => $todayNotification->notificationtype,
                'notificationtypeid' => $todayNotification->notificationtypeid,
                'notificationcategory' => $todayNotification->notificationcategory,
            ];
        });
 
        // //Older Notification
        // Notification::where('typeId', $typeId)->update(['isRead' => true]);
        // $olderNotification = Notification::where('typeId', $typeId)
        //     ->where(function ($query) {
        //         $query->whereDate('createdAt', '!=', Carbon::today());
        //     })
        //     ->where(function ($query) {
        //         $query->whereNull('broadcast_id') // Exclude notifications with a broadcast_id
        //             ->orWhere(function ($query) {
        //                 $query->whereNotNull('broadcast_id')
        //                     ->where('createdAt', '>=', Carbon::now()->subHours(24));
        //             });
        //     })
        //     ->orderByRaw('CASE WHEN notifications.broadcast_id IS NOT NULL THEN 0 ELSE 1 END, "notifications"."createdAt" DESC')
        //     ->paginate(10, ['*'], 'older_page', $currentOlderPage);
        // $olderNotifications = $olderNotification->map(function ($olderNotification) use ($leaderFollowers,$partyFollowers){
        //     $formattedDate = $olderNotification->createdAt->diffForHumans();
        //     $profileImage = null;
 
        //     if ($olderNotification->userId) {
        //         $user = User::where('id', $olderNotification->userId)->with('userDetails')->first();
 
        //         if ($user) {
        //             $profileImage = optional($user->userDetails)->profileImage;
        //         } else {
        //             $party = Party::where('id', $olderNotification->userId)->first();
        //             $profileImage = optional($party)->logo;
        //         }
        //     } else {
        //         $profileImage = $olderNotification->profileImage;
        //     }
        //     $isFollowing = null;
        //     if($olderNotification->userType == "Party"){
        //         $isFollowing = $partyFollowers->contains($olderNotification->userId);
        //     }else {
        //         $isFollowing = $leaderFollowers->contains($olderNotification->userId);
        //     }
        //     $userc = User::find($olderNotification->createdBy);
        //     $userType = $userc->getRoleNames()[0];
        //     $broadcast_id = null;
        //     $broadcast_name = null;
        //     $broadcast_broadcastTitle = null;
        //     $broadcast_message = null;
        //     $broadcast_image = null;
        //     $broadcast_url = null;
        //     $broadcast_hashtags = null;
        //     $broadcast_createdByType = null;
        //     $broadcast_createdBy = null;
        //     if ($olderNotification->broadcast_id) {
        //         $broadcast = Broadcast::find($olderNotification->broadcast_id);
        //         if ($broadcast) {
        //             $broadcast_id = $broadcast->id;
        //             $broadcast_user = User::find($broadcast->createdBy);
        //             $broadcast_name = $broadcast_user ? $broadcast_user->firstName. ' ' . $broadcast_user->lastName : null;
        //             $broadcast_broadcastTitle = $broadcast->broadcastTitle;
        //             $broadcast_message = $broadcast->broadcastMessage;
        //             $broadcast_image = $broadcast->image;
        //             $broadcast_url = $broadcast->url;
        //             $broadcast_hashtags = $broadcast->hashtags;
        //             $broadcast_createdByType = $broadcast->createdByType;
        //             $broadcast_createdBy = $broadcast->createdBy;
        //         }
        //     }
 
        //     return [
        //         'id' => $olderNotification->id,
        //         'notificationIcon' =>  $profileImage,
        //         'notificationMessage' => $olderNotification->notificationMessage,
        //         'createdAt' => $formattedDate,
        //         'isFollowing' => $isFollowing,
        //         'createdByType' => $userType,
        //         'broadcast_id' => $broadcast_id,
        //         'broadcast_name' => $broadcast_name,
        //         'broadcast_title' => $broadcast_broadcastTitle,
        //         'broadcast_message' => $broadcast_message,
        //         'broadcast_image' => $broadcast_image,
        //         'broadcast_url' => $broadcast_url,
        //         'broadcast_hashtags' => $broadcast_hashtags,
        //         'broadcast_createdByType' => $broadcast_createdByType,
        //         'broadcast_createdBy' => $broadcast_createdBy,
        //     ];
        // });

        // Construct pagination URLs
        // $todayNotificationPaginationUrls = [];
        // for ($page = 1; $page <= $todayNotification->lastPage(); $page++) {
        //     $todayNotificationPaginationUrls[] = $todayNotification->url($page);
        // }
        
        // $olderNotificationsPaginationUrls = [];
        // for ($page = 1; $page <= $olderNotification->lastPage(); $page++) {
        //     $olderNotificationsPaginationUrls[] = $olderNotification->url($page) . '&type=older';
        // }

        // Get the last page number for todayNotifications
        $todayLastPage = $todayNotification->lastPage();

        // Get the last page number for olderNotifications
        // $olderLastPage = $olderNotification->lastPage();
        
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications',
            'Notification' => $todayNotifications,
            'NotificationPagination' => [
                'current_page' => $todayNotification->currentPage(),
                'last_page' => $todayLastPage,
                // 'pagination_urls' => $todayNotificationPaginationUrls,
            ],
            // 'olderNotifications' => $olderNotifications,
            // 'olderNotificationsPagination' => [
            //     'current_page' => $olderNotification->currentPage(),
            //     'last_page' => $olderLastPage,
            //     'pagination_urls' => $olderNotificationsPaginationUrls,
            // ],
        ], 200);

        // return response()->json(['status' => 'success', 'message' => 'All notifications', 'todayNotification' => $todayNotifications, 'olderNotifications' => $olderNotifications], 200);
 
    }
    /**
     * @OA\Get(
     *     path="/api/notReadNotification",
     *     summary="Fetch all notReadNotification",
     *     tags={"Notification"},
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
     *       description="Error / Data not found "
     *   ),
     *      security={{ "apiAuth": {} }}
     * )
     */
    public function notReadNotification()
    {
        $partyId = request('partyId');
        $typeId = $partyId ? $partyId : Auth::user()->id;
        $unReadNotificationCount = Notification::where('typeId', $typeId)->where('isRead', false)->count();
        return response()->json(['status' => 'success', 'message' => 'All notifications', 'unReadNotification' => $unReadNotificationCount], 200);
 
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
    public function store(Request $request)
    {
        //
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
 