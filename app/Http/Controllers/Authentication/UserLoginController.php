<?php

namespace App\Http\Controllers\Authentication;


use Carbon\Carbon;
use App\Models\User;
use App\Models\Party;
use App\Models\State;
use App\Models\Leader;
use App\Helpers\Search;
use App\Models\TempData;
use App\Models\deviceKey;
use App\Models\UserToken;
use App\Helpers\AesCipher;
use App\Models\UserAddress;
use App\Models\UserDetails;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Models\PartyFollowers;
use App\traits\BroadcastCount;
use App\Models\AssemblyDetails;
use App\Models\LeaderFollowers;
use App\Models\LoksabhaDetails;
use App\Models\UserFollowerTag;
use App\Models\UserLogActivity;
use App\Helpers\EncryptionHelper;
use App\Models\TimeSpendDuration;
use Illuminate\Support\Facades\DB;
use App\Models\AssemblyConsituency;
use App\Models\LokSabhaConsituency;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\RegistrationController\RegisterController;

class UserLoginController extends Controller
{
    use BroadcastCount;
    /**
     * Display a listing of the resource.
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }
    /**
     * @OA\Post(
     * path="/api/login",
     * operationId="authLogin",
     * tags={"Authentication"},
     * summary="User Login",
     * description="Login User Here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"userName", "password"},
     *               @OA\Property(property="userName", type="string", example=""),
     *               @OA\Property(property="password", type="string", format="password", example="")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function login(Request $request)
    {
        try {
            $credentials = request(['userName', 'password']);
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['status' => 'error', 'message' => "Wrong Username or Password"], 404);
            }

            $user = auth()->user();
            $user->lastLogin = Carbon::now();
            $user->loginCount++;
            $user->save();

            UserToken::create([
                'userId' => $user->id,
                'token' => $token,
            ]);
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Something went wrong please try again later"], 404);
        }
    }
    public function me()
    {
        return response()->json(auth()->user());
    }
    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="User Logout",
     *     tags={"Authentication"},
     *     operationId="authLogout",
     *     @OA\Response(
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
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function logout(Request $request)
    {
        $deviceId = $request->deviceId;
        if ($deviceId != "") {
            UserLogActivity::where('deviceId', $deviceId)->delete();
            auth()->logout();
            return response()->json(['message' => 'Successfully logged out']);
        }
    }
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }
    /**
     * @OA\Get(
     *     path="/api/tokenVerify",
     *     summary="Check if user need to clear or now",
     *     tags={"Authentication"},
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
    public function tokenVerify()
    {
        $user = Auth::user();
        if ($user->tokenVerify == true) {
            $user->tokenVerify = false;
            $user->save();
            return response()->json([
                'message' => 'Your session has been terminated for security reasons. Please log in again.',
                'clear_token' => true,
            ]);
        } else {
            return response()->json([
                'message' => 'User Token is Verified.',
                'clear_token' => false,
            ]);
        }
    }
    /**
       * @OA\Get(
       *     path="/api/preferences",
       *     summary="Get Preferences",
       *     tags={"Authentication"},
       
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
    public function preferences(Request $request)
    {
        try {
            $keyword = request('keyword');
            $consituencyId = request('consituencyId');
            $user = User::where('id', Auth::user()->id)->first();
            $mergedResults = collect();
            
            if ($user) {
                $parties = Party::where('name', 'ILIKE', "%$keyword%")
                    ->orWhere('nameAbbrevation', 'ILIKE', "%$keyword%")
                    ->select('name', 'logo', 'id')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();
                foreach ($parties as $party) {
                    $party['type'] = 'Party';
                }

                $mergedResults = $mergedResults->concat($parties);

                $assembly = AssemblyConsituency::where('name', 'ILIKE', "%$keyword%")
                    ->where('id', 'ILIKE', "%$consituencyId%")
                    ->select('name', 'id', 'logo')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get();

                $assembly = $assembly->map(function ($item) {
                    $item->type = 'Constituency';
                    return $item;
                });

                $mergedResults = $mergedResults->concat($assembly);

                $loksabha = LokSabhaConsituency::where('name', 'ILIKE', "%$keyword%")
                    ->where('id', 'ILIKE', "%$consituencyId%")
                    ->select('name', 'id', 'logo')
                    ->inRandomOrder()->limit(3)
                    ->get();

                $loksabha = $loksabha->map(function ($item) {
                    $item->type = 'Constituency';
                    return $item;
                });

                $mergedResults = $mergedResults->concat($loksabha);

                $leader =   User::role('Leader')->join('user_details', 'users.id', '=', 'user_details.userId')
                            ->where(function ($query) use ($keyword) {
                                $query->where('firstName', 'ILIKE', "%$keyword%")
                                ->orWhere('lastName', 'ILIKE', "%$keyword%")
                                ->orWhere('userName', 'LIKE', "%$keyword%");
                            });

                if($consituencyId && $consituencyId != '') {
                    $leader->where('assemblyId', $consituencyId)->orWhere('loksabhaId', $consituencyId);
                }

                $leaders =  $leader->select('users.id', DB::raw("CONCAT(\"firstName\", ' ', \"lastName\") AS name"),
                            'user_details.profileImage as logo', DB::raw("'Leader' as type"))->inRandomOrder()
                            ->limit(3)->where('status', 'Active')->get();
                
                $mergedResults = $mergedResults->concat($leaders);
            }

            return response()->json(['status' => 'success', 'message' => 'First Time Preferences', 'result' => $mergedResults], 200);
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

   /**
 * @OA\Post(
 *     path="/api/submitPreferences",
 *     summary="Add new role",
 *     tags={"Authentication"},
 *     @OA\RequestBody(
 *         required=true,
 *         description="Enter details",
 *         @OA\JsonContent(
 *             required={"preferencesDetails", "constituencyId"}, 
 *             @OA\Property(property="preferencesDetails",
 *                 type="object",
 *                 required={"preferPageId", "preferPageType"},
 *                 @OA\Property(property="preferPageId", type="string", example="sdsd343"),
 *                 @OA\Property(property="preferPageType", type="string", example="Consituency")
 *             ),
 *             @OA\Property(property="constituencyId", type="string", example="your_constituency_id_value_here") 
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
 *         description="Error / Data not found"
 *     ),
 *     security={{ "apiAuth": {} }}
 * )
 */
    public function submitPreferences(Request $request)
    {
        try {
            DB::beginTransaction();
            $constituencyId = $request->constituencyId;
            $preferences = $request->preferencesDetails;
            $user = User::find(Auth::user()->id);
            $userType = $user->getRoleNames()[0];
            
            foreach ($preferences as $prefer) {
                $preferType = $prefer['preferPageType'];
                $preferId = $prefer['preferPageId'];
                if ($preferType == 'Party') {
                    $party = Party::where('id', $preferId)->first();
                    $createPartyFollower = [
                        'followerId' => Auth::user()->id,
                        'partyId' => $party->id,
                        'createdBy' => Auth::user()->id,
                        'updatedBy' => Auth::user()->id,
                    ];
                    PartyFollowers::create($createPartyFollower);
                    $party->followercount++;
                    $party->save();
                }

                if ($preferType == 'Leader') {
                    $leader = Leader::where('leadersId', $preferId)->first();
                    $createLeaderFollower = [
                        'leaderId' => $preferId,
                        'followerId' => Auth::user()->id,
                        'createdBy' => Auth::user()->id,
                        'updatedBy' => Auth::user()->id,
                    ];
                    LeaderFollowers::create($createLeaderFollower);
                    $leader->followercount++;
                    $leader->save();
                }

                if ($preferType == 'Constituency') {
                    $consituencyType = 'Assembly';
                    $consituency = AssemblyConsituency::where('id', $preferId)->first();
                    if ($consituency == '') {
                        $consituency = LokSabhaConsituency::where('id', $preferId)->first();
                        $consituencyType = 'LokSabha';
                    }
                    $consituency->followercount++;
                    $consituency->save();
                    $createConsituencyFollowerTag = [
                        'userId' => Auth::user()->id,
                        'followedTags' => $consituency->name,
                        'createdBy' => Auth::user()->id,
                        'updatedBy' => Auth::user()->id,
                        'assembly_id' => ($consituencyType == 'Assembly') ? $consituency->id : null,
                        'loksabha_id' => ($consituencyType == 'LokSabha') ? $consituency->id : null ,
                        'userType' => $userType,
                    ];
                

                    if($preferId != $constituencyId ){
                        UserFollowerTag::create($createConsituencyFollowerTag);
                        if($consituencyType == 'Assembly'){
                            $this->insertOrUpdateUserAssemblyFollowersForBroadCast($preferId, $user->gender,$user->DOB,true, true);
                        } else {
                            $this->insertOrUpdateUserLokSabhaForBroadCast($preferId, $user->gender,$user->DOB,true, true);
                        }

                    }
                }
            }

            // ConstituencyId should be only Assembly 
            if( $constituencyId != '')
            {
               $assembly = AssemblyConsituency::find($constituencyId);
               
                $user = User::find(Auth::user()->id);
                $userDetails = UserDetails::where('userId',$user->id)->first();
                $assembylyId = $assembly->id;
                $loksabhaId = $assembly->loksabhaId;
                if (!$userDetails) {
                    $userDetails = new UserDetails();                    
                    $userDetails->userId = $user->id;
                    $userDetails->createdBy = $user->id;
                    $userDetails->updatedBy = $user->id;
                    $userDetails->assemblyId = $constituencyId;
                    $userDetails->loksabhaId = $loksabhaId;
                    $userDetails->save();
                }
                else {
                    $userDetails->assemblyId = $constituencyId;
                    // $loksabhaId = $assembly->id;
                    $userDetails->loksabhaId = $loksabhaId;
                    $userDetails->save();
                }
                // $userDetails->assemblyId = $assembly->id;
                 $this->insertOrUpdateUserAssemblyForBroadCast($assembylyId, $user->gender,$user->DOB,true);
                 $this->insertOrUpdateUserLokSabhaForBroadCast($loksabhaId, $user->gender,$user->DOB,true);
                 $consituencyname = $assembly->name;
                 $userId = Auth::user()->id;
                 $consituencyFollower = UserFollowerTag::where('followedTags', $consituencyname)->where('userId', Auth::user()->id)->exists();
                 if ($consituencyFollower) {
                    $deleted = UserFollowerTag::where('userId', $userId)
                         ->where('followedTags', $consituencyname)
                         ->delete();
                 } else {
                    $createConsituencyFollowerTag = [
                         'userId' => Auth::user()->id,
                         'followedTags' => $consituencyname,
                         'createdBy' => Auth::user()->id,
                         'updatedBy' => Auth::user()->id,
                         'assembly_id' => $assembylyId,
                         'userType' => $userType,
                     ];
                    //  UserFollowerTag::create($createConsituencyFollowerTag);
                 }
               }
            //    else
            //    {

            //     // We need to get Only Assembly details from user to update it's loksabha details, 
            //     $loksabha = LokSabhaConsituency::find($constituencyId);
            //     $user = User::find(Auth::user()->id);
            //     $userDetails = UserDetails::where('userId',$user->id)->first();
            //     if (!$userDetails) {
            //         $userDetails = new UserDetails();
            //         $userDetails->userId = $user->id;
            //         $userDetails->createdBy = $user->id;
            //         $userDetails->updatedBy = $user->id;
            //         $userDetails->loksabhaId = $constituencyId;
            //         $userDetails->save();
            //     }
            //     else{
            //         $userDetails->loksabhaId = $constituencyId;
            //         $userDetails->save();
            //     }
            //     // $userDetails->loksabhaId =  $loksabha->id;
            //     $this->insertOrUpdateUserLokSabhaForBroadCast($constituencyId, $user->gender, $user->DOB, true);
            //    }
          
            // }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => 'Preferences selected'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    
    /**
     * @OA\Post(
     *     path="/api/saveTimeSpend",
     *     summary="Save Time Spend",
     *     tags={"User Activity"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"timeSpend","startTime","endTime"},
     *         @OA\Property(property="startTime", type="string", example=5),
     *        @OA\Property(property="endTime", type="string", example=5),
     *       @OA\Property(property="timeSpend", type="string", example=5),
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

    public function saveTimeSpend(Request $request)
    {
        $userId = Auth::user()->id;
        $startTime = strval($request->startTime);
        $endTime = strval($request->endTime);
        $startTime = Carbon::parse($startTime);
        $endTime = Carbon::parse($endTime);
        $minutesDifference = $endTime->diffInMinutes($startTime);
        $currentDate = now()->format('Y-m-d');

        $existingRecord = TimeSpendDuration::where('userId', $userId)
            ->where('date', $currentDate)
            ->first();

        if ($existingRecord) {
            $existingRecord->update(['timeSpend' => $existingRecord->timeSpend + $minutesDifference]);
        } else {
            TimeSpendDuration::create([
                'userId' => $userId,
                'date' => $currentDate,
                'timeSpend' => $minutesDifference,
                "createdBy" => Auth::user()->id,
                "updatedBy" => Auth::user()->id
            ]);
        }

        return response()->json(['status' => 'success', 'message' => 'TimeSpend updated or created successfully'], 200);
    }
    /**
     * @OA\Post(
     *     path="/api/saveLoginActivity",
     *     summary="Save Login Activity",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"location","deviceType","ipAddress","ipAddressOwner","deviceId"},
     *         @OA\Property(property="location", type="string", example="location"),
     *        @OA\Property(property="deviceType", type="string", example="deviceType"),
     *        @OA\Property(property="ipAddress", type="string", example="ipAddress"),
     *        @OA\Property(property="ipAddressOwner", type="string", example="ipAddressOwner"),
     *        @OA\Property(property="deviceId", type="string", example="deviceId"),
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


    public function saveLoginActivity(Request $request)
    {
        try {
            $location = $request->location;
            $deviceType = $request->deviceType;
            $ipAddress = $request->ipAddress;
            $ipAddressOwner = $request->ipAddressOwner;
            $deviceId = $request->deviceId;
            $deviceInfo = [
                "userId" => Auth::user()->id,
                "location" => $location,
                "deviceType" => $deviceType,
                "ipAddress" => $ipAddress,
                "ipAddressOwner" => $ipAddressOwner,
                "deviceId" => $deviceId,
                "status" => "Active",
                "createdBy" => Auth::user()->id,
                "updatedBy" => Auth::user()->id,
            ];
            UserLogActivity::create($deviceInfo);
            return response()->json(['status' => 'success', 'message' => 'Login Activity Saved'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/saveFCMToken",
     *     summary="Save Token ",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"fcmToken"},
     *         @OA\Property(property="fcmToken", type="string", example="fcmToken"),
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
    public function saveToken(Request $request)
    {
        $fcmToken = $request->fcmToken;

        $userId = Auth::user()->id;

        $existingDeviceKey = DeviceKey::where('userId', $userId)->first();

        if ($existingDeviceKey) {
            $existingDeviceKey->update([
                'userId' => Auth::user()->id,
                'userdeviceKey' => trim($fcmToken),
                'updatedBy' => $userId,
            ]);
        } else {
            DeviceKey::create([
                'userId' => $userId,
                'userdeviceKey' => $fcmToken,
                'createdBy' => $userId,
                'updatedBy' => $userId,
            ]);
        }
        return response()->json(['status' => 'success', 'message' => 'Key Saved'], 200);

    }

    /**
     * @OA\Post(
     *     path="/api/aadharsubmitOTP",
     *     summary="Verify Aadhar OTP",
     *     tags={"Registration"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Register a new user",
     *     @OA\JsonContent(
     *         required={"transId","OTP"},
     *             @OA\Property(property="transId", type="string", example="S-HPK-441788"),
     *             @OA\Property(property="otp", type="number", example="123456"),
     *             @OA\Property(property="aadharNumber", type="string", format="aadharNumber", example="1234567890"),
     *             @OA\Property(property="aadharNumberupdate", type="boolean", example=false)
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

     public function aadharsubmitOTP(Request $request)
     {
        $apiEndpoint = "https://www.truthscreen.com/v1/apicall/nid/aadhar_submit_otp";
         $username = 'production@yugasys.com';
         $iv = AesCipher::getIV();
         $requestData = [
             'transId' => $request->transId,
             'otp' => $request->otp,
 
         ];
         $aadharUsername = env('AADHAR_USERNAME', 'Bharath@0923');
         $encrypted = AesCipher::encrypt($aadharUsername, $iv, json_encode($requestData));
         $requestData = [
             'requestData' => $encrypted,
         ];
         try {
             $response = Http::withHeaders([
                 'Content-Type' => 'application/json',
                 'username' => $username,
             ])->post($apiEndpoint, $requestData);
             $responseData = $response['responseData'];
             $aadharResponse = AesCipher::decrypt($aadharUsername, $responseData);
             $aadharResponse = json_decode($aadharResponse, true);
             $status = $aadharResponse['status'];
             $msg = $aadharResponse['msg'];
 
             if ($status == 1) {
                $user = Auth::user();
                $userId = $user->id;
                $nameParts = explode(' ', $aadharResponse['msg']['Name']);
                $firstName = count($nameParts) >= 2 ? $nameParts[0] : $aadharResponse['msg']['Name'];
                $lastName = count($nameParts) >= 2 ? end($nameParts) : '';
                $userData = [
                    'status' => 'Active',
                    'lastLogin' => now(),
                    'firstName' => !empty($firstName) ? $firstName : null,
                    'lastName' => !empty($lastName) ? $lastName : null,
                    'aadharNumber' => !empty($request->aadharNumber) ? EncryptionHelper::encryptString($request->aadharNumber) : null,
                ];
                $existingUser = User::find($userId);
                $existingUser->update(['firstName' => $userData['firstName']]);
                empty($existingUser->lastName) && $existingUser->update(['lastName' => $userData['lastName']]);
                empty($existingUser->aadharNumber) && $existingUser->update(['aadharNumber' => $userData['aadharNumber']]);
                empty($existingUser->status) && $existingUser->update(['status' => $userData['status']]);
                empty($existingUser->lastLogin) && $existingUser->update(['lastLogin' => $userData['lastLogin']]);

                $addressData = [
                    'userId' => $user->id,
                    'address' => $aadharResponse['msg']['Address'],
                    'state' => $aadharResponse['msg']['State'],
                    'district' => $aadharResponse['msg']['District'],
                    'cityTown' => $aadharResponse['msg']['Village/Town/City'],
                    'pinCode' => $aadharResponse['msg']['Pincode'],
                ];
                $existingAddress = UserAddress::where('userId', $userId)->first();
                empty($existingAddress->address) && $existingAddress->update(['address' => $addressData['address']]);
                empty($existingAddress->state) && $existingAddress->update(['state' => $addressData['state']]);
                empty($existingAddress->district) && $existingAddress->update(['district' => $addressData['district']]);
                empty($existingAddress->cityTown) && $existingAddress->update(['cityTown' => $addressData['cityTown']]);
                empty($existingAddress->pinCode) && $existingAddress->update(['pinCode' => $addressData['pinCode']]);

                $state = $aadharResponse['msg']['State'];
                $stateModel = State::where('name', $state)->first();
                $dob = $aadharResponse['msg']['DOB'];
                $gender = trim($aadharResponse['msg']['Gender']);
                if ($gender == 'MALE' || $gender == 'M' || $gender == 'Male' || $gender == 'male'){
                    $gender = 'MALE';
                } else if ($gender == 'FEMALE' || $gender == 'F' || $gender == 'Female' || $gender == 'female'){
                    $gender = 'FEMALE';
                } else {
                    $gender = 'OTHERS';
                }
                // $registrationController = new RegisterController();
                $this->insertOrUpdateUserstateForBroadCast($stateModel->id, $gender, $dob,true);
             }
            
             return $status == "1"
                 ? response()->json(['status' => 'success', 'message' => 'Aadhar Verified Successfully','isAadharPresent' => true], 200)
                 : response()->json(['status' => 'error', 'message' => $msg,], 400);
         } catch (\Exception $e) {
             return ($e->getMessage());
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
 
         }
 
     }
}