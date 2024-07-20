<?php

namespace App\Http\Controllers\RegistrationController;

use App\Helpers\EncryptionHelper;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\TempData;
use App\Models\User;
use Crypt;
use Illuminate\Http\Request;
use App\Helpers\AesCipher;
use Http;




class AadharController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/aadhaarVerify",
     *     operationId="registration",
     *     tags={"Registration"},
     *     summary="User registration",
     *     description="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         description="User registration data",
     *         @OA\JsonContent(
     *             required={"aadharNo"},
     *             @OA\Property(property="aadharNumber", type="string", example="123456781234")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="",
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
    public function aadhaarVerify(Request $request)
    {
        try {
            $aadharNo = $request->aadharNumber;
            $encryptedAadhar = EncryptionHelper::encryptString($aadharNo);
            $potentialMatches = User::where('aadharNumber', 'like', '%' . substr($encryptedAadhar, 0, 10) . '%')->get();

            foreach ($potentialMatches as $potentialMatch) {
                $decryptedPotentialMatch = EncryptionHelper::decryptString($potentialMatch->aadharNumber);

                if ($decryptedPotentialMatch === $aadharNo) {
                    return response()->json(['status' => 'error', 'message' => "Aadhar number already registered, please login"], 404);
                }
            }

            $aadharUsername = "Bharath@0923";
            $apiEndpoint = "https://www.truthscreen.com/v1/apicall/nid/aadhar_get_otp";
            $username = "production@yugasys.com";
            $iv = AesCipher::getIV();
            $transID = "12A31";
            $docType = 211;

            $requestData = [
                'transID' => $transID,
                'docType' => $docType,
                'aadharNo' => $aadharNo,
            ];

            $encrypted = AesCipher::encrypt($aadharUsername, $iv, json_encode($requestData));
            $requestData = [
                'requestData' => $encrypted,
            ];

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'username' => $username,
            ])->post($apiEndpoint, $requestData);

            $responseData = $response['responseData'];

            $responseMessage = AesCipher::decrypt($aadharUsername, $responseData);
            $data = json_decode($responseMessage, true);
            $status = $data['status'];
            $msg = $data['msg'];
            if ($status == 1) {
                return $responseMessage;
            }

            return $status == "1"
                ? response()->json(['status' => 'success', 'message' => 'Aadhar Verified Successfully'], 200)
                : response()->json(['status' => 'error', 'message' => $msg], 400);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }

    }





    /**
     * @OA\Post(
     *     path="/api/submitotp",
     *     operationId="verifyOTP",
     *     tags={"Registration"},
     *     summary="Verify Aadhar OTP",
     *     description="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         description="Verify Aadhar OTP",
     *         @OA\JsonContent(
     *             required={"transId","OTP"},
     *             @OA\Property(property="transId", type="string", example="S-HPK-441788"),
     *             @OA\Property(property="otp", type="number", example="123456"),
     *             @OA\Property(property="aadharNumber", type="string", format="aadharNumber", example="1234567890")
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

    public function submitOTP(Request $request)
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

            $aadharResponses = json_decode($aadharResponse, true);
            $status = $aadharResponses['status'];
            $msg = $aadharResponses['msg'];
            if ($status == 1) {
                TempData::create(['userDetails' => $aadharResponse, 'aadharNo' => $request->aadharNumber]);
            }

            return $status == "1"
                ? response()->json(['status' => 'success', 'message' => 'Aadhar Verified Successfully'], 200)
                : response()->json(['status' => 'error', 'message' => $msg], 401);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);

        }

    }


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

    public function store(Request $request)
    {

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