<?php

namespace App\Http\Controllers\FactCheck;

use App\Helpers\Action;
use App\Helpers\EncryptionHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FactCheck;
use App\Models\User;
use App\Models\FactBuster;
use App\Models\PostByLeader;
use App\Helpers\LogActivity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class FactCheckController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     /**
     * @OA\Get(
     *     path="/api/factCheck",
     *     summary="Fetch all Fact Check",
     *     tags={"Fact Check"},
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
        try {
            $currentPage = request('page', 1);
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $userId = Auth::user()->id;
            $userType = Auth::user()->getRoleNames()[0];
            $FactCheckAll = FactCheck::where('userId', $userId)->get();
            $factCheckArray = $FactCheckAll->map(function ($getFactCheck) {
                $userId = $getFactCheck->userId;
                $user = User::find($userId);
                $userName = $user->userName;
                $userType = $user->getRoleNames()[0];
                return [
                    'id' => $getFactCheck->id,
                    'userId' => $userId,
                    'userName' => $userName,
                    'userType' => $userType,
                    'userProfile' => $user->userDetails->profileImage,
                    'subject' => $getFactCheck->subject,
                    'description' => $getFactCheck->description,
                    'hashTag' => $getFactCheck->hashTag,
                    'url' => $getFactCheck->url,
                    'status'=> $getFactCheck->status,
                    'media' => explode(',', $getFactCheck->media)
                ];
            });
            $desiredTotal = $factCheckArray->count();
            $pagedPosts = $factCheckArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Fact Check Lists', 'result' => $list], 200);
        } catch (\Exception $e) {
            // return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
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
     *     path="/api/factCheck",
     *     summary="Add Fact Check",
     *     tags={"Fact Check"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Fact Check",
    *       @OA\JsonContent(
    *           required={"userId","postId","subject","description","hashTag","url","media"},
    *           @OA\Property(property="subject", type="string", example="testing"),
    *           @OA\Property(property="description", type="string", example="description"),
    *           @OA\Property(property="hashTag", type="string", example="#hashTag"),
    *           @OA\Property(property="url", type="string", example="http://welcome.com"),
    *          @OA\Property(
    *                 property="media",
    *                 type="array",
    *                 @OA\Items(type="string", example="profile.jpg, profile1.jpg"),
    *                 description="Multiple Images"
    *          ),
    *       ),
     *     ),
     *
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
    public function store(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $userType = Auth::user()->getRoleNames()[0];
            $postId = $request->input('postId') ?? null;
            $isAdmin = (Auth::user()->role === 'Super Admin') ? "true" : "false";
            $subject = $request->input('subject');
            $description = $request->input('description');
            $hashTag = $request->input('hashTag');
            $url = $request->input('url');
            $media = $request->input('media') ?? [];
            $mediaString = implode(',', $media);

            FactCheck::create([
                'userId' => $userId,
                'postId' => $postId,
                'userType' => $userType,
                'subject' => $subject,
                'description' =>  $description,
                'hashTag' =>  $hashTag,
                'url' =>  $url,
                'media' =>  $mediaString,
                'isCreatedByAdmin' => $isAdmin,
                'status' => 'Pending',
                'createdBy' => $userId,
                'updatedBy' => $userId,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);

            $user = auth()->user();

            /* ============================= sending email ========================================== */
            $template = Action::getTemplate('fact check');
            $adminUser = User::role('Super Admin')->first();
            $fullName = $adminUser->getFullName();
            if($template['type']=="template") {
                foreach($template['data'] as $d){
                    $content = str_replace(["{AdminName}", "{userName}"], [$fullName, $user->getFullName()], $d->content);
                    $mail['content']=$content;
                    $mail['email']=$adminUser->email;
                    $mail['subject']=$d->subject;
                    $mail['fileName']="template";
                    $mail['cc']='';
                    if($d->cc!=null){
                        $mail['cc']=$d->cc;
                    }                  
                }
                Action::sendEmail($mail);
            }

            // /* ============================= sending push notification ============================== */
            // $userId = $adminUser->userDetails->userId;
            // $replaceArray = ['{name}' => Auth::user()->getFullName()];
            // $notificationType = 'fact_check';
            // $UserType = 'Admin';
            // $notificationTitle = Action::getNotification('superAdmin', $notificationType);
            // Action::createNotification($userId, $UserType, $adminUser->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $notificationTitle));
            
            
            return response()->json(['status' => 'success', 'message' => 'Your Request has been Sent Succesfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Display the specified resource.
    */
    /**
     * @OA\Get(
     *     path="/api/factCheck/{id}",
     *     summary="Fetch Fact Check by id",
     *     tags={"Fact Check"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
     *     ),
     *     @OA\Response(
     *       response="400",
     *       description="Bad Request",
     *   ),
     *   @OA\Response(
     *       response="401",
     *       description="Data not found",
     *   ),
     *  security={{ "apiAuth": {} }}
     * )
     */
    public function show(string $id)
    {
        try{
            $getFact = FactCheck::find($id);
            if (!$getFact) {
                return response()->json(['status' => 'error', 'message' => 'Data not Found'], 200);
            }

            $get_fact_data = [
                'title' => $getFact->subject,
                'description' => $getFact->description,
            ];
            $getUserMedia = $this->separateMedia($getFact->media);
            $user_data = [
                'id' => $getFact->id,
                'title' => $getFact->subject,
                'hashTag' => $getFact->hashTags,
                'description' => $getFact->postDescriptions,
                'url' => $getFact->url,
                'otherMedia' => $getUserMedia['otherMedia'],
                'attachments' => $getUserMedia['attachments'],
                'status' => $getFact->status
            ];
           
            $getFactBuster = FactBuster::where("factId", $id)->first();
            $getAdminMedia = $this->separateMedia($getFactBuster->attachments);
            $admin_data = ($getFactBuster) ? [
                'id' => $getFactBuster->id,
                'title' => $getFactBuster->title,
                'hashTag' => $getFactBuster->hashTags,
                'description' => $getFactBuster->description,
                'url' => $getFactBuster->external_link, 
                'otherMedia' => $getAdminMedia['otherMedia'],
                'attachments' => $getAdminMedia['attachments'],
            ] : [];

            $shareUrl = env('APP_URL') . '/sharedPost/' . EncryptionHelper::encryptString($id) . "?postByType=".str_replace(' ', '-', "Super Admin");    
            $result = [
                'get_fact_data' =>$get_fact_data,
                'user_data' => $user_data,
                'admin_data' => $admin_data,
                'factTruth' => $getFactBuster->fact ?? $getFact->status,
                'shareUrl' => $shareUrl,
            ];
            
            return response()->json(['status' => 'success','message' => 'View Fact Check','result'=>$result],200);                       
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);
        }
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

    /**
     *   @OA\Put(
     *   path="/api/factCheck/{id}",
     *     summary="Update Fact Check by id",
     *     tags={"Fact Check"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"subject","description","hashTag","url","media"},
     *        @OA\Property(property="subject", type="string", example="testing"),
     *        @OA\Property(property="description", type="string", example="description"),
     *        @OA\Property(property="hashTag", type="string", example="#hashTag"),
     *        @OA\Property(property="url", type="string", example="http://welcome.com"),
     *        @OA\Property(property="media", type="string", example="profile.jpg"),
     *        
     *      ),
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Error while updating "
     *   ),
     *    security={{ "apiAuth": {} }}
     *)
     **/
    public function update(Request $request, string $id)
    {
        try {
            $factCheck = FactCheck::find($id);
    
            if (!$factCheck) {
                return response()->json(['status' => 'error', 'message' => 'Fact Check not found'], 404);
            }
    
            $subject = $request->subject ?? $factCheck->subject;
            $description = $request->description ?? $factCheck->description;
            $hashTag = $request->hashTag ?? $factCheck->hashTag;
            $url = $request->url ?? $factCheck->url;
            $media = $request->media ?? $factCheck->media;    
            $factCheck->subject = $subject;
            $factCheck->description = $description;
            $factCheck->hashTag = $hashTag;
            $factCheck->url = $url;
            $factCheck->media = $media;

            $factCheck->save();
    
            return response()->json(['status' => 'success', 'message' => 'Fact Check updated successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    function separateMedia($mediaString) {
        $mediaArr = explode(',', $mediaString);
    
        $attachments = [];
        $otherMedia = [];
    
        foreach ($mediaArr as $mediaUrl) {
            // Check if the URL ends with '.pdf'
            if (pathinfo($mediaUrl, PATHINFO_EXTENSION) === 'pdf') {
                // PDF file, add to attachments
                $attachments[] = trim($mediaUrl);
            } else {
                // Not a PDF, add to other media
                $otherMedia[] = trim($mediaUrl);
            }
        }
    
        return ['attachments' => $attachments, 'otherMedia' => $otherMedia];
    }

}