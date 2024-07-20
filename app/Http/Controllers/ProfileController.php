<?php

namespace App\Http\Controllers;

use Auth;
use Hash;
use App\Models\User;
use App\Helpers\Action;
use App\Models\UserToken;
use App\Helpers\HttpHelper;
use App\Models\RoleUpgrade;
use App\Models\UserAddress;
use App\Models\UserDetails;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\EncryptionHelper;
use App\Helpers\CheckPagePermission;
use Illuminate\Support\Facades\Validator;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Auth user profile details",
     *     tags={"Profile"},
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
    public function index()
    {
        try {
            $user = User::where('id', Auth::user()->id)->select('id', 'userName', 'firstName', 'lastName', 'aadharNumber', 'pinCode', 'email', 'phoneNumber', 'DOB', 'gender', 'address', 'cityTown', 'state', 'voterId', 'privacy')->first();
            $aadharNumber = EncryptionHelper::decryptString($user->aadharNumber);
            $user['aadharNumber'] =  $aadharNumber;
            $user['userType'] = Auth::user()->getRoleNames()[0];
            $userDetails = UserDetails::where('userId', Auth::user()->id)
                ->select('isVoterIdVerified', 'voterImage', 'loksabhaId', 'assemblyId', 'boothId', 'profileImage')
                ->first();
            $userDetails = UserDetails::where('userId', Auth::user()->id)
                ->select('isVoterIdVerified', 'voterImage', 'loksabhaId', 'assemblyId', 'boothId', 'profileImage')
                ->first();
            $address = UserAddress::where('userId', Auth::user()->id)->select('address', 'state', 'district', 'cityTown', 'pinCode')->first();
            $user['profileImage'] = ($userDetails != '') ? $userDetails->profileImage : '';
            $electionData = ['isVoterIdVerified' => $userDetails->isVoterIdVerified ?? '', 'loksabha' => ($userDetails != '') ? $userDetails->getLokSabhaName() : '', 'assembly' => ($userDetails != '') ? $userDetails->getAssemblyName() : '', 'booth' => ($userDetails != '') ? $userDetails->getboothName() : '',];
            $permenanetAddress = ['address' => $address->address ?? '', 'state' => $address->state ?? '', 'district' => $address->district ?? '', 'cityTown' => $address->cityTown ?? '', 'pinCode' => $address->pinCode ?? ''];
            $electionData['voterId'] = $user->voterId;
            $electionData['voterImage'] = $userDetails->voterImage ?? '';
            $checkUpgrade = RoleUpgrade::where('requestedBy', $user->id)->first();
            $user['subscriptions'] = false;
            if ($checkUpgrade) {
                $user['verificationStatus'] = $checkUpgrade->requestStatus;
                $user['validTill'] = $checkUpgrade->validTill;
            } else {
                $user['verificationStatus'] = null;
                $user['validTill'] = null;
            }

            if ($user) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Profile Details',
                    'result' => [
                        'user' => $user,
                        'electionDetails' => $electionData,
                        'permanentAddress' => $permenanetAddress,
                    ],
                ], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }
        } catch (\Exception $e) {
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
    /**
     *   @OA\Put(
     *   path="/api/profile/{id}",
     *     summary="Update profile details",
     *     tags={"Profile"},
     *       @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"privacy","DOB","gender","state","email" , "district", "cityTown", "pinCode", "phoneNumber"},
     *         @OA\Property(property="privacy", type="boolean", example="1/0",description="If privacy data is send no other details will be updated "),
     *         @OA\Property(property="DOB", type="string", example="12/23/01"),
     *         @OA\Property(property="Profile", type="string", example="Imagename"),
     *         @OA\Property(property="gender", type="string", example="Male"),
     *        @OA\Property(property="state", type="string", example="Haryana"),
     *        @OA\Property(property="email", type="string", example="email@email.com"),
     *        @OA\Property(property="address", type="string", example="address"),
     *        @OA\Property(property="district", type="string", example="Testdistrict"),
     *        @OA\Property(property="cityTown", type="string", example="City"),
     *        @OA\Property(property="pinCode", type="string", example="1234567"),
     *        @OA\Property(property="phoneNumber", type="string", example="123655232"),
     *        @OA\Property(property="voterImage", type="string", example="voterImageUrl"),
     *       @OA\Property(property="voterId", type="string", example="CRL34F"),
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
            $user = User::find($id);
            if ($request->filled('privacy')) {
                $privacyValue = $request->input('privacy');
                if ($privacyValue === '1' || $privacyValue === '0') {
                    $user->privacy = $privacyValue;
                    $user->save();
                    return response()->json(['status' => 'success', 'message' => 'Privacy Settings Updated'], 200);

                } else {
                    return response()->json(['status' => 'error', 'message' => 'Invalid privacy value.'], 400);
                }
            }
            $rules = [
                'email' => [

                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phoneNumber' => [
                    Rule::unique('users')->ignore($user->id),
                ],
            ];

            $messages = [
                'email.unique' => 'The email address is already in use by another user.',
                'phoneNumber.unique' => 'The phone number is already in use by another user.',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
            }
            // if ($request->input('email') === $user->email || $request->input('phoneNumber') === $user->phoneNumber) {
            //     return response()->json(['status' => 'error', 'message' => 'You are already registered with this email or phone number.'], 400);
            // }
            if ($request->has('Profile')) {
                $verifyImage = HttpHelper::checkImage($request->input('Profile'));
                $verifyImage = str_replace('"', '', $verifyImage);
                if ($verifyImage == "vulgar") {
                    return response()->json(['status' => 'error', 'message' => "Profile image was not appropriate"], 404);
                }
            }
            $dob = $request->input('DOB') ?? $user->DOB;
            $gender = $request->input('gender') ?? $user->gender;
            $state = $request->input('state') ?? $user->state;
            $email = $request->input('email') ?? $user->email;
            $district = $request->input('district') ?? $user->district;
            $cityTown = $request->input('cityTown') ?? $user->cityTown;
            $pinCode = $request->input('pinCode') ?? $user->pinCode;
            $phoneNumber = $request->input('phoneNumber') ?? $user->phoneNumber;
            $address = $request->input('address') ?? $user->address;
            $voterId = $request->input('voterId') ?? $user->voterId;

            $userDetails = UserDetails::where('userId', $user->id)->first();
            $profileImage = $userDetails != '' ? $request->input('Profile') ?? $userDetails->profileImage : null;
            $voterImage = $userDetails != '' ? $request->input('voterImage') ?? $userDetails->voterImage : null;

            $user->fill([
                'DOB' => $dob,
                'gender' => $gender,
                'state' => $state,
                'email' => $email,
                'district' => $district,
                'cityTown' => $cityTown,
                'pinCode' => $pinCode,
                'phoneNumber' => $phoneNumber,
                'address' => $address,
                'voterId' => $voterId,
            ]);

            $user->save();

            UserDetails::updateOrInsert(
                ['userId' => $user->id],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'profileImage' => $profileImage,
                    'voterImage' => $voterImage,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id
                ]
            );
            if ($request->input('voterId') != '' || $request->input('voterImage') != '') {                
                $userDetails = User::find($id);
                $citizenName = $userDetails->getFullName();
                $adminUser = User::role('Super Admin')->first();
                $adminName = $adminUser->getFullName();
                //Notification to Admin
                $notificationType = 'voteriduploaded';
                $getNotification = Action::getNotification('admin', $notificationType);
                $replaceArray = ['{name}' => $citizenName];
                $userId = $userDetails->userDetails ? $userDetails->userDetails->userId : null;
                Action::createNotification($userId, 'Admin', $adminUser->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
                //Notification to Admin End

               $template = Action::getTemplate('voterid uploaded');

                if ($template['type'] == "template") {
                    foreach ($template['data'] as $d) {
                        $content = str_replace(["{adminName}", "{citizenName}"], [$adminName, $citizenName,], $d->content);
                        $mail['content'] = $content;
                        $mail['email'] = $adminUser->email;
                        $mail['subject'] = $d->subject;
                        $mail['fileName'] = "template";
                        $mail['cc'] = '';
                        if ($d->cc != null) {
                            $mail['cc'] = $d->cc;
                        }

                    }
                    Action::sendEmail($mail);
                }
            }
            return response()->json(['status' => 'success', 'message' => 'Profile details updated'], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function addElection(Request $request)
    {
        try {
            $userId = auth()->user()->id;
            $user = User::find($userId);


            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            $user->voterId = $request->electionid;
            $user->save();

            $voterImagePath = $request->voterImage;
            if ($voterImagePath != '') {
                $userDetails = UserDetails::firstOrNew(['userId' => $user->id, 'createdBy' => $user->id, 'updatedBy' => $user->id]);
                $userDetails->voterImage = $voterImagePath;
                $userDetails->save();
            }

            return response()->json(['status' => 'success', 'message' => 'Election Id added Successfully'], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
   * @OA\Post(
   *     path="/api/reset_password",
   *     summary="Update Password",
   *     tags={"Profile"},
   *     @OA\RequestBody(
   *      required=true,
   *      description="Enter details",
   *     @OA\JsonContent(
   *         required={"currentPassword","newPassword","newPassword_confirmation"},
   *         @OA\Property(property="currentPassword", type="string", example="password"),
   *        @OA\Property(property="newPassword", type="string", example="password"),
   *        @OA\Property(property="newPassword_confirmation", type="string", example="password"),
   *        @OA\Property(property="logoutOtherDevice", type="0/1", example=0),

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
    public function passwordUpdate(Request $request)
    {
        try {
            $request->validate([
                'newPassword' => 'required|confirmed'
            ]);
            $user = Auth::user();
            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json(['status' => 'error', 'message' => "New password must be different from the current password"], 400);
            }
            if (Hash::check($request->currentPassword, $user->password)) {
                $user->password = Hash::make($request->newPassword);
                $user->save();

                // $template = Action::getTemplate('Password Update');
                // $users = User::find($user->id);
                // if ($template['type'] == "template") {
                //     foreach ($template['data'] as $d) {
                //         $content = str_replace("{citizenName}", $users->getFullName(), $d->content);
                //         $mail['content'] = $content;
                //         $mail['email'] = $users->email;
                //         $mail['subject'] = $d->subject;
                //         $mail['fileName'] = "template";
                //         $mail['cc'] = '';
                //         if ($d->cc != null) {
                //             $mail['cc'] = $d->cc;
                //         }

                //     }
                //     Action::sendEmail($mail);
                // }
                

               
                return response()->json(['status' => 'success', 'message' => "Password Updated Sucessfully"], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => "Current Password doesn't match"], 404);
            }
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }
}