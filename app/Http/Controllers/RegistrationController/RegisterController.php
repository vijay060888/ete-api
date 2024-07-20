<?php

namespace App\Http\Controllers\RegistrationController;

use Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Party;
use App\Models\State;
use App\Helpers\Action;
use App\Models\TempData;
use App\Models\UserAddress;
use App\Models\UserDetails;
use App\Helpers\LogActivity;
use App\Models\StateDetails;
use Illuminate\Http\Request;
use App\traits\BroadcastCount;
use App\Helpers\EncryptionHelper;
use App\Models\PhoneVerification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use BroadcastCount;
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Post(
     *     path="/api/suggestUsername",
     *     operationId="suggestUser",
     *     tags={"Registration"},
     *     summary="Suggest username",
     *     description="Suggest Username",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Suggest Username",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function suggestUsername(Request $request)
    {
        try {
            $name = $request->name;
            $baseUsername = strtolower(str_replace(' ', '', $name));
            $suggestedUsernames = [];
            while (count($suggestedUsernames) < 5) {
                $randomNumber = rand(pow(10, 2), pow(10, 4) - 1);
                $suggestedUsername = $baseUsername . $randomNumber;
                if (!User::where('userName', $suggestedUsername)->exists()) {
                    $suggestedUsernames[] = $suggestedUsername;
                }
            }
            return response()->json(['status' => 'sucecss', 'message' => "Suggested Name", "result" => $suggestedUsernames], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/checkUsernameAvailability",
     *     operationId="CheckUser",
     *     tags={"Registration"},
     *     summary="Check Username Availability",
     *     description="Check Username Availability",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"userName"},
     *             @OA\Property(property="userName", type="string", example="name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="check Username",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function checkUsernameAvailability(Request $request)
    {
        try {
            $username = $request->userName;
            $userExists = User::where('userName', $username)->exists();
            return $userExists
                ? response()->json(['status' => 'error', 'message' => false], 400)
                : response()->json(['status' => 'success', 'message' => true], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
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
     *     path="/api/register",
     *     operationId="CompleteRegistration",
     *     tags={"Registration"},
     *     summary="Complete Registration",
     *     description="Complete user registration",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"email", "phoneNumber", "userName","aadharNumber" ,"password"},
     *             @OA\Property(property="userName", type="string", example="name"),
     *             @OA\Property(property="phoneNumber", type="string", example="1234567890"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="aadharNumber", type="string", format="aadharNumber", example="1234567890"),
     *            @OA\Property(property="password", type="string", format="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource Not Found",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function store(Request $request)
    {

        try {

        $rules = [
            'email' => 'required|email|unique:users',
            'phoneNumber' => 'required|unique:users',
            'userName' => 'required|unique:users',
            'aadharNumber' => 'required|unique:users',
            'password' => 'required'
        ];

        // try {
        //     $getTemplate = Action::getSMSTemplate('Registration Successfull');
        //     foreach ($getTemplate['data'] as $d) {
        //         $content = str_replace("{#var#}", "Anil", $d->template);
        //         $details['template'] = $content;
        //         $details['tempid'] = $d->tempid;
        //         $details['entityid'] = $d->entityid;
        //         $details['source'] = $d->source;
        //     }

        //     Action::sendSMS($details, $request->input('phoneNumber'));


        // } catch (\Exception $e) {

        // }
        // return "send";


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'message' => $validator->errors()->first()], 400);
        }
        $email = $request->email;
        $userTemp = TempData::where('aadharNo', $request->aadharNumber)->first(); //temp solution for now
        if ($userTemp != '') {
            $hashedPassword = Hash::make($request->input('password'));
            $aadharResponse = $userTemp->userDetails;
            $aadharResponse = json_decode($aadharResponse, true);
            $nameParts = explode(' ', $aadharResponse['msg']['Name']);
            $firstName = count($nameParts) >= 2 ? $nameParts[0] : $aadharResponse['msg']['Name'];
            $lastName = count($nameParts) >= 2 ? end($nameParts) : '';
            $fullName = $firstName . " " . $lastName;
            $gender = trim($aadharResponse['msg']['Gender']);
            if ($gender == 'MALE' || $gender == 'M' || $gender == 'Male' || $gender == 'male'){
                $gender = 'MALE';
            } else if ($gender == 'FEMALE' || $gender == 'F' || $gender == 'Female' || $gender == 'female'){
                $gender = 'FEMALE';
            } else {
                $gender = 'transgender';
            }

            $userData = [
                'userName' => $request->input('userName'),
                'firstName' => $firstName,
                'lastName' => $lastName,
                'aadharNumber' => EncryptionHelper::encryptString($request->aadharNumber),
                'password' => $hashedPassword,
                'gender' =>   $gender,
                'DOB' => $aadharResponse['msg']['DOB'],
                'phoneNumber' => $request->input('phoneNumber'),
                'email' => $request->input('email'),
                'educationPG' => $request->input('educationPG'),
                'educationUG' => $request->input('educationUG'),
                'profesionalExperience' => $request->input('profesionalExperience'),
                'profesionalDepartment' => $request->input('profesionalDepartment'),
                'salary' => $request->input('salary'),
                'status' => 'Active',
                'forgotPassword' => 0,
                'loginCount' => 0,
                'loginLogin' => now(),
                'privacy' => $request->input('privacy'),
            ];
            $user = User::create($userData);
            $user->assignRole('Citizen');
            $addressData = [
                'userId' => $user->id,
                'address' => $aadharResponse['msg']['Address'],
                'state' => $aadharResponse['msg']['State'],
                'district' => $aadharResponse['msg']['District'],
                'cityTown' => $aadharResponse['msg']['Village/Town/City'],
                'pinCode' => $aadharResponse['msg']['Pincode'],
            ];
            $userDetails = [
                'userId' => $user->id,
                'createdBy' => $user->id,
                'updatedBy' => $user->id,
            ];
            UserDetails::create($userDetails);
            UserAddress::create($addressData);
            $template = Action::getTemplate('Registration Successfull');
            $data = $template['data'];
            if ($template['type'] == "template") {
                foreach ($data as $d) {
                    $content = str_replace("{citizenName}", $userTemp->firstName, $d->content);
                    $mail['content'] = $content;
                    $mail['email'] = $email;
                    $mail['subject'] = $d->subject;
                    $mail['fileName'] = "template";
                    $mail['cc'] = '';
                    if ($d->cc != null) {
                        $mail['cc'] = $d->cc;
                    }

                }
                Action::sendEmail($mail);

                try {
                    $getTemplate = Action::getSMSTemplate('Registration Successfull');
                    foreach ($getTemplate['data'] as $d) {
                        $content = str_replace("{#var#}", $fullName, $d->template);
                        $details['template'] = $content;
                        $details['tempid'] = $d->tempid;
                        $details['entityid'] = $d->entityid;
                        $details['source'] = $d->source;
                    }

                    Action::sendSMS($details, $request->input('phoneNumber'));


                } catch (\Exception $e) {

                }

                $state = $aadharResponse['msg']['State'];
                $gender = $aadharResponse['msg']['Gender'];
                $stateModel = State::where('name', $state)->first();
                $dob = $aadharResponse['msg']['DOB'];
                
                if ($gender == 'MALE' || $gender == 'M' || $gender == 'Male' || $gender == 'male'){
                    $gender = 'MALE';
                } else if ($gender == 'FEMALE' || $gender == 'F' || $gender == 'Female' || $gender == 'female'){
                    $gender = 'FEMALE';
                } else {
                    $gender = 'OTHERS';
                }
               $this->insertOrUpdateUserstateForBroadCast($stateModel->id, $gender, $dob,true);
        }
           
            TempData::where('id', $userTemp->id)->delete();

            return response()->json(['message' => 'User registered successfully'], 200);
        } else {
            return response()->json(['status' => 'error', 'message' => "Something went wrong start verification from first"], 404);
        }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }


    // function insertOrUpdateUserstateForBroadCast($stateId, $gender, $dob, $increment) {
    //     $stateExists = StateDetails::where('stateId', $stateId)->where('gender', $gender)->first();
    //     if(!$stateExists) {
    //         $ageRangeArr = $this->updateAgeRangeKey(['10' => 0, '20'=> 0, '30'=> 0, '40'=> 0, '50' => 0, '60' => 0, '70'=> 0, '80'=> 0], $dob, $increment);
    //         StateDetails::create([
    //             'stateId' => $stateId,
    //             'gender' => $gender,
    //             'ageRange' => json_encode($ageRangeArr),
    //             'user_count' => 1,
    //         ]);
    //     } else {
    //         $ageRangeArr = $this->updateAgeRangeKey(json_decode($stateExists->ageRange, true), $dob, $increment);
    //         $stateExists->update([
    //             'ageRange' => json_encode($ageRangeArr),
    //             'user_count' => ($increment) ? ($stateExists->user_count + 1) : ($stateExists->user_count - 1),
    //         ]);
    //     }
    //     return true;
    // }
    // function updateAgeRangeKey($ageRangeArr, $dob, $increment) {
    //     $birthdate = Carbon::parse($dob);
    //     $currentDate = Carbon::now();
    //     $userAge = $currentDate->diffInYears($birthdate);
    //     $AgeKey = floor($userAge / 10) * 10;
    //     if (isset($ageRangeArr[$AgeKey])) {
    //         $ageRangeArr[$AgeKey] = ($increment) ? ($ageRangeArr[$AgeKey] + 1) : ($ageRangeArr[$AgeKey] - 1);
    //     }        
    //     return $ageRangeArr;
    // }
    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/phoneSignupUsernamePassword",
     *     operationId="CompleteRegistration by phone signup",
     *     tags={"Registration"},
     *     summary="Complete Registration by phone signup",
     *     description="Complete user registration by phone signup",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data by phone signup",
     *         @OA\JsonContent(
     *             required={"userId","userName","password"},
     *             @OA\Property(property="userId", type="string", example="userId"),
     *             @OA\Property(property="name", type="string", example="name"),
     *             @OA\Property(property="userName", type="string", example="Username"),
     *             @OA\Property(property="dob", type="string", example="2024-01-13"),
     *             @OA\Property(property="gender", type="string", example="MALE"),
     *            @OA\Property(property="password", type="string", format="string", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource Not Found",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function phoneSignupUsernamePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'userId' => 'required|string',
                'userName' => 'required|string',
                'name' => 'required|string',
                'password' => 'required|string',
                'dob' => 'required|date_format:Y-m-d|before_or_equal:' . Carbon::now()->subYears(16)->format('Y-m-d'),
                'gender' => 'required',
            ], [
                'dob.before_or_equal' => 'You must be at least 16 years old to register.',
                'dob.date_format' => 'The date of birth must be in the format yyyy-mm-dd.',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 422);
            }

            $user = User::find($request->input('userId'));

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
            }

            $user->firstName = $request->input('name');
            $user->userName = $request->input('userName');
            $user->DOB = Carbon::createFromFormat('Y-m-d', $request->input('dob'))->format('d-m-Y');
            $gender = $request->input('gender');
            if ($gender === 'THIRD GENDER') {
                $gender = 'OTHERS';
            }
            $user->gender = $gender;
            $user->password = Hash::make($request->input('password'));
            $user->save();

            return response()->json(['status' => 'success', 'message' => 'Registration successful'], 201);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/phoneSignup",
     *     operationId="phoneSignup",
     *     tags={"Registration"},
     *     summary="phoneSignup",
     *     description="Complete user registration",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"phoneNumber"},
     *             @OA\Property(property="phoneNumber", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Registration successful",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Resource Not Found",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function phoneSignup(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'phoneNumber' => 'required|digits:10',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
            }
           $phoneNumber = $request->input('phoneNumber');
           $user = User::where('phoneNumber', $phoneNumber)->first();
            if (!$user) {
                $randomNumber = mt_rand(100000, 999999);
                $createOTP = [
                    "phoneNumber" => $phoneNumber,
                    "otp" => $randomNumber,
                ];
                PhoneVerification::where('phoneNumber', $phoneNumber)->delete();
                PhoneVerification::create($createOTP);
                //SMS Logic here
                $getTemplate = Action::getSMSTemplate('soneta reset the password');
                foreach ($getTemplate['data'] as $d) {
                    $content = str_replace("{#var#}", $randomNumber, $d->template);
                    $details['template'] = $content;
                    $details['tempid'] = $d->tempid;
                    $details['entityid'] = $d->entityid;
                    $details['source'] = $d->source;
                }
               $getAction = Action::sendSMS($details, $phoneNumber);
               
               return ($getAction == 1)
               ? response()->json(['status' => 'success', 'message' => "OTP sent to your requested phone number", "phoneNumber" => $phoneNumber], 200)
               : response()->json(['status' => 'error', 'message' => "Something went wrong. Please try again", "phoneNumber" => $phoneNumber], 400);
            } else { 
                return response()->json(['status' => 'error', 'message' => 'Already registered with these number', 'data' => $user], 404);
            }
            return response()->json(['status' => 'success', 'phoneNumber' => $phoneNumber, 'otp' => 'Otp sent Successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/phoneSubmitotp",
     *     operationId="verifyPhoneOTP",
     *     tags={"Registration"},
     *     summary="Verify Phone OTP",
     *     description="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Verify Phone OTP",
     *         @OA\JsonContent(
     *             required={"transId","OTP"},
     *             @OA\Property(property="otp", type="number", example="123456"),
     *             @OA\Property(property="phoneNumber", type="string", format="phoneNumber", example="9876543210")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="OTP Send To Verify",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * )
     */

    public function phoneSubmitOTP(Request $request)
    {
        
        try {
            $validator = Validator::make($request->all(), [
                'phoneNumber' => 'required|digits:10',
                'otp' => 'required|digits:6',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
            }
            $phoneVerification = PhoneVerification::where('phoneNumber', $request->input('phoneNumber'))->first();
            if (!$phoneVerification) {
                return response()->json(['status' => 'error', 'message' => 'Phone number not found'], 404);
            } else if ($phoneVerification->otp != $request->input('otp')) {
                return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 400);
            } else {
                $password = Hash::make($request->input('phoneNumber'));
                $userData = [
                    'userName' => "",
                    'firstName' => "",
                    'lastName' => "",
                    'aadharNumber' => "",
                    'password' => $password,
                    'gender' => "",
                    'DOB' => "",
                    'phoneNumber' => $request->input('phoneNumber'),
                    'email' => null,
                    'educationPG' => "",
                    'educationUG' => "",
                    'profesionalExperience' => "",
                    'profesionalDepartment' => "",
                    'salary' => "",
                    'status' => 'Active',
                    'forgotPassword' => 0,
                    'loginCount' => 0,
                    'loginLogin' => now(),
                    'privacy' => "",
                ];
                $user = User::create($userData);
                $user->assignRole('Citizen');
                UserDetails::create([
                    'userId' => $user->id,
                    'voterImage' => null,
                    'profileImage' => null,
                    'loksabhaId' => null,
                    'assemblyId' => null,
                    'boothId' => null,
                    'createdBy' => $user->id,
                    'updatedBy' => $user->id
                ]);
                UserAddress::create([
                    'userId' => $user->id
                ]);
                $phoneVerification->delete();
            }
           
            return response()->json(['status' => 'success', 'message' => 'User registration successfully', 'user' => $user], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }


    public function verifyPhoneOtp(Request $request)
    {
        
        try {
            $validator = Validator::make($request->all(), [
                'phoneNumber' => 'required|digits:10',
                'otp' => 'required|digits:6',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()->first()], 400);
            }
            $phoneVerification = PhoneVerification::where('phoneNumber', $request->input('phoneNumber'))->first();
            if (!$phoneVerification) {
                return response()->json(['status' => 'error', 'message' => 'Phone number not found'], 404);
            }
            else if($phoneVerification->otp != $request->input('otp')) {
                return response()->json(['status' => 'error', 'message' => 'Invalid OTP'], 400);
            } else {
                $phoneVerification->delete();
                return response()->json(['status' => 'success', 'message' => 'User registration successfully'], 200);    
            }
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


    public function updateValues($input, $increment)
{
    $result = [];

    foreach ($input as $key => $value) {
        if ($value < 10) {
            $result[$key] = $value + 1;
        } elseif ($value >= 10) {
            // Limit the increase to not go beyond 100
            $result[$key] = min(100, $value + 10);
        }
    }

    // Add a new key if not found and limit the new key to not go beyond 100
    $newKey = min(100, max(array_keys($input)) + $increment);
    $result[$newKey] = 1;

    return $result;
}

}