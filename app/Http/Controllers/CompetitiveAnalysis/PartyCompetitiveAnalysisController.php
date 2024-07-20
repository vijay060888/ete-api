<?php

namespace App\Http\Controllers\CompetitiveAnalysis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogActivity;
use App\Models\Party;
use App\Models\PartyDetails;
use App\Models\PartyFollowers;
use Auth;
use App\Models\User;
use App\Helpers\Calculation;
use Illuminate\Support\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;

class PartyCompetitiveAnalysisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/partyCompetetiveAnalysis",
     *     summary="Party Competetive Analysis",
     *     tags={"Party Competetive Analysis"},
     *  @OA\Parameter(
    *         name="party_id",
    *         in="query",
    *         description="ID of the party",
    *         required=true,
    *         @OA\Schema(
    *             type="string",
    *             format="uuid"
    *         )
    *     ),
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
    public function index(Request $request)
    {
        try {
            $partyId = $request->input('party_id');
            $partyDetails = Party::where('id', $partyId)->get();
            return response()->json(['status' => 'success', 'message' => "Party Details", "result" => $partyDetails], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/getOtherParties",
     *     summary="Get Other Parties",
     *     tags={"Party Competetive Analysis"},
     * @OA\Parameter(
    *         name="party_id",
    *         in="query",
    *         description="ID of the party",
    *         required=true,
    *         @OA\Schema(
    *             type="string",
    *             format="uuid"
    *         )
    *     ),
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
     
     public function getOtherParties(Request $request)
     {
        $perPage = 10;
        $partyId = $request->input('party_id');
        $otherParties = Party::where('id', '!=', $partyId)->paginate($perPage);
         $otherPartiesData = $otherParties->map(function ($party) use($otherParties) {
             $name = $party->name;
             $nameAbbrevation = $party->nameAbbrevation;
             $logo = $party->logo;
             $type = $party->type;
 
             return [
                 'id' => $party->id,
                 'name' => $name,
                 'nameAbbrevation' => $nameAbbrevation,
                 'logo' => $logo,
                 'type' => $type,
                 'currentPage' => $otherParties->currentPage(),
             ];
         });
         $currentPage = $otherParties->currentPage();
            $lastPage = $otherParties->lastPage();
         return response()->json(['status' => 'success', 'message' => 'Party Profile details', 'result' => $otherPartiesData,'current_page' => $currentPage,
         'last_page' => $lastPage], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/compareParties",
     *     summary="Party Competetive Analysis",
     *     tags={"Party Competetive Analysis"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="partyId",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="partyId", type="string", format="partyId", example="partyId"),
     *                 ),
     *                 example={
     *                     {
     *                         "partyId": "partyId3"
     *                     },
     *                     {
     *                         "partyId": "partyId2"
     *                     }
     *                 },
     *                 description="Array of party IDs"
     *             )
     *         )
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

     public function compareParties(Request $request)
     {
         try {
            $partysId = $request->partyId;
            $partyDetails = [];
            foreach ($partysId as $partyId) {
                $comparision = [
                    'partyId' => $partyId['partyId'],
                    'logo' => null,
                    'followersCount' => null,
                    'youngUsers' => null,
                    'middledAgeUsers' => null,
                    'maleFollowers' => null,
                    'femaleFollowers' => null,
                    'transgenderFollowers' => null,
                    'appreciatePostCount' => null,
                    'likePostCount' => null,
                    'carePostCount' => null,
                    'unlikesPostCount' => null,
                    'sadPostCount' => null,
                    'issuedResolvedCount' => null,
                    'postFrequency' => null,
                    'sentiments' => null,
                    'responseTime' => null,
                ];
                $partyDetail = PartyDetails::where('partyId', $partyId['partyId'])->first();
                $parties = Party::where('id', $partyId['partyId'])->first();
                if ($partyDetail) {
                    $partyFollowerIds = PartyFollowers::where('partyId', $partyId['partyId'])->pluck('followerId');
                    $youngAgeCount = 0;
                    $middleAgeCount = 0;
                    $maleCount = 0;
                    $femaleCount = 0;
                    foreach ($partyFollowerIds as $followerId) {
                        $user = User::find($followerId);
            
                        if ($user) {
                            $dob = $user->DOB;
            
                            if ($dob) {
                                $age = Carbon::parse($dob)->age;
            
                                if ($age <= 35) {
                                    $youngAgeCount++;
                                } elseif ($age > 35) {
                                    $middleAgeCount++;
                                }
                            }
            
                            $gender = strtoupper($user->gender);
                            if ($gender == 'MALE') {
                                $maleCount++;
                            } elseif ($gender == 'FEMALE') {
                                $femaleCount++;
                            }
                        }
                    }
                    $postFrequency = Calculation::calculatePostFrequencyParty($partyId['partyId']);
                    $comparision = [
                        'partyId' => $partyId['partyId'],
                        'logo' => $parties->logo, // Placeholder for profile image
                        'followersCount' => count($partyFollowerIds),
                        'youngUsers' => $youngAgeCount,
                        'middledAgeUsers' => $middleAgeCount,
                        'maleFollowers' => $maleCount,
                        'femaleFollowers' => $femaleCount,
                        'transgenderFollowers' => $partyDetail->transgenderFollowers,
                        'appreciatePostCount' => $partyDetail->appreciatePostCount,
                        'likePostCount' => $partyDetail->likePostCount,
                        'carePostCount' => $partyDetail->carePostCount,
                        'unlikesPostCount' => $partyDetail->unlikesPostCount,
                        'sadPostCount' => $partyDetail->sadPostCount,
                        'issuedResolvedCount' => $partyDetail->issuedResolvedCount,
                        'postFrequency' => $postFrequency,
                        'sentiments' => $partyDetail->sentiments,
                        'responseTime' => $partyDetail->responseTime
                    ];
                    $partyDetails[] = $comparision;
                }
            }
            return response()->json(['status' => 'success', 'message' => "Party Competetive Analysis", 'result' => $partyDetails], 200);
         } catch (\Exception $e) {
             \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
         }
     }

     /**
     * @OA\Post(
     *     path="/api/generatePartyAnalysisReport",
     *     summary="Generate Party Analysis Report",
     *     tags={"Party Competetive Analysis"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="partyId",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="partyId", type="string", format="partyId", example="partyId"),
     *                 ),
     *                 example={
     *                     {
     *                         "partyId": "partyId3"
     *                     },
     *                     {
     *                         "partyId": "partyId2"
     *                     }
     *                 },
     *                 description="Array of party IDs"
     *             )
     *         )
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
    public function generatePartyAnalysisReport(Request $request)
    {
        try {
            $partysId = $request->partyId;
            $partyDetails = [];
            foreach ($partysId as $partyId) {
                $comparision = [
                    'partyId' => $partyId['partyId'],
                    'partyname' => null,
                    'logo' => null,
                    'followersCount' => null,
                    'youngUsers' => null,
                    'middledAgeUsers' => null,
                    'maleFollowers' => null,
                    'femaleFollowers' => null,
                    'transgenderFollowers' => null,
                    'appreciatePostCount' => null,
                    'likePostCount' => null,
                    'carePostCount' => null,
                    'unlikesPostCount' => null,
                    'sadPostCount' => null,
                    'issuedResolvedCount' => null,
                    'postFrequency' => null,
                    'sentiments' => null,
                    'responseTime' => null,
                ];
                $partyDetail = PartyDetails::where('partyId', $partyId['partyId'])->first();
                $parties = Party::where('id', $partyId['partyId'])->first();
                if ($partyDetail) {
                    $partyFollowerIds = PartyFollowers::where('partyId', $partyId['partyId'])->pluck('followerId');
                    $youngAgeCount = 0;
                    $middleAgeCount = 0;
                    $maleCount = 0;
                    $femaleCount = 0;
                    foreach ($partyFollowerIds as $followerId) {
                        $user = User::find($followerId);
            
                        if ($user) {
                            $dob = $user->DOB;
            
                            if ($dob) {
                                $age = Carbon::parse($dob)->age;
            
                                if ($age <= 35) {
                                    $youngAgeCount++;
                                } elseif ($age > 35) {
                                    $middleAgeCount++;
                                }
                            }
            
                            $gender = strtoupper($user->gender);
                            if ($gender == 'MALE') {
                                $maleCount++;
                            } elseif ($gender == 'FEMALE') {
                                $femaleCount++;
                            }
                        }
                    }
                    $postFrequency = Calculation::calculatePostFrequencyParty($partyId['partyId']);
                    $comparision = [
                        'partyId' => $partyId['partyId'],
                        'partyname' => $parties->name,
                        'logo' => $parties->logo,
                        'followersCount' => count($partyFollowerIds),
                        'youngUsers' => $youngAgeCount,
                        'middledAgeUsers' => $middleAgeCount,
                        'maleFollowers' => $maleCount,
                        'femaleFollowers' => $femaleCount,
                        'transgenderFollowers' => $partyDetail->transgenderFollowers,
                        'appreciatePostCount' => $partyDetail->appreciatePostCount,
                        'likePostCount' => $partyDetail->likePostCount,
                        'carePostCount' => $partyDetail->carePostCount,
                        'unlikesPostCount' => $partyDetail->unlikesPostCount,
                        'sadPostCount' => $partyDetail->sadPostCount,
                        'issuedResolvedCount' => $partyDetail->issuedResolvedCount,
                        'postFrequency' => $postFrequency,
                        'sentiments' => $partyDetail->sentiments,
                        'responseTime' => $partyDetail->responseTime
                    ];
                    $partyDetails[] = $comparision;
                }
            }

            $pdf = PDF::loadView('reports.partyreports', ['partyDetails' => $partyDetails]);
            $fileName = 'report-'.mt_rand(1000000000, 9999999999) . '.pdf';
            $filePath = 'reports/'.$fileName; 
            $pdf->setPaper('a2', 'landscape');
            if (!is_dir(public_path('reports'))) {
                mkdir(public_path('reports'), 0777, true);
            }

            if ($pdf->save(public_path($filePath))) {
                $url = env('APP_URL') . '/' . $filePath;
                return response()->json(['status' => 'success', 'message' => "File Generated", 'url' => $url], 200);
            } else {
                throw new Exception("Failed to generate the PDF");
            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }
}
