<?php

namespace App\Http\Controllers\Authentication;

use App\Helpers\Action;
use App\Http\Controllers\Controller;
use App\Models\OTPVerification;
use App\Models\User;
use Hash;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
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
     *     path="/api/forgotPassword",
     *     summary="Add phone number",
     *     tags={"Forget Password"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"phonenumber"},
     *         @OA\Property(property="phoneNumber", type="integer", example=1234567890),
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
    public function store(Request $request)
    {
        $phoneNumber = $request->phoneNumber;

        $user = User::where('phoneNumber', $phoneNumber)->first();

        if ($user) {
            OTPVerification::where('phoneNumber', $phoneNumber)->delete();
            $randomNumber = mt_rand(100000, 999999);

            $createOTP = [
                "phoneNumber" => $phoneNumber,
                "otp" => $randomNumber,
            ];
            OTPVerification::create($createOTP);
            //SMS Logic here
            $getTemplate = Action::getSMSTemplate('Reset Password');
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

            return response()->json(['status' => 'error', 'message' => "User doesn't exits"], 400);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/verifyOTP",
     *     summary="Verify OTP",
     *     tags={"Forget Password"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"phoneNumber","OTP"},
     *         @OA\Property(property="phoneNumber", type="integer", example=1234567890),
     *         @OA\Property(property="OTP", type="integer", example=123456),
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
    public function verifyOTP(Request $request)
    {
        $phoneNumber = $request->phoneNumber;
        $otp = $request->OTP;
        $storedOTP = OTPVerification::where('phoneNumber', $phoneNumber)->first();
        if ($storedOTP) {
            if ($otp == $storedOTP->otp) {
                $storedOTP->delete();
                return response()->json(['status' => 'success', 'message' => "OTP is Valid", "phoneNumber" => $phoneNumber], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => "Invalid OTP"], 404);
            }
        } else {
            return response()->json(['status' => 'error', 'message' => "OTP Expired"], 404);

        }
    }

    /**
     * @OA\Post(
     *     path="/api/resetPassword",
     *     summary="Reset Password",
     *     tags={"Forget Password"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"phoneNumber","newPassword"},
     *         @OA\Property(property="phoneNumber", type="integer", example=1234567890),
     *         @OA\Property(property="newPassword", type="integer", example="password"),
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
    public function resetPassword(Request $request)
    {
        $phoneNumber = $request->phoneNumber;
        $newPassword = $request->newPassword;

        $user = User::where('phoneNumber', $phoneNumber)->first();

        if ($user) {
            if (Hash::check($request->newPassword, $user->password)) {
                return response()->json(['status' => 'error', 'message' => "New password must be different from the current password"], 400);
            }
            $user->password = Hash::make($newPassword);
            $user->save();

            return response()->json(['message' => 'Password updated successfully'], 200);
        } else {
            return response()->json(['error' => 'User not found'], 404);
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