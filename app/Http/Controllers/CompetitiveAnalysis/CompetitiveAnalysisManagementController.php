<?php

namespace App\Http\Controllers\CompetitiveAnalysis;

use Auth;
use App\Models\User;
use App\Models\Leader;
use App\Models\LogActivity;
use App\Models\UserDetails;
use App\Models\Party;
use App\Helpers\Calculation;
use App\Models\PostByLeader;
use Illuminate\Http\Request;
use App\Models\LeaderDetails;
use App\Models\LeaderFollower;
use Illuminate\Support\Carbon;
use App\Models\LeaderCoreParty;
use App\Models\LeaderFollowers;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Exception;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Storage;

class CompetitiveAnalysisManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/competetiveAnalysis",
     *     summary="Competetive Analysis",
     *     tags={"Competetive Analysis"},
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
            $userId = Auth::user()->id;
            $leaderDetails = Leader::where('leadersId', $userId)
                ->with('user', 'leaderDetails', 'leaderMinistry', 'getLeaderCoreParty.party')
                ->first();
            if ($leaderDetails->getLeaderCoreParty != '') {
                $partyName = $leaderDetails->getLeaderCoreParty->party->name;
            } else {
                $partyName = null;
            }
            $leaderDetails = [
                'leaderId' => $userId,
                'leaderName' => $leaderDetails->user->firstName . ' ' . $leaderDetails->user->lastName,
                'leaderProfile' => $leaderDetails->leaderDetails->profileImage,
                'leaderMinistry' => $leaderDetails->leaderElectedRole,
                'partyName' => $partyName
            ];
            return response()->json(['status' => 'success', 'message' => "Leader Details", "result" => $leaderDetails], 200);

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
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Get(
     *     path="/api/getOtherLeaders",
     *     summary="Get Other Leaders",
     *     tags={"Competetive Analysis"},
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




    public function getOtherLeaders()
    {
        $currentUser = auth()->user();
        $otherLeaders = User::role('Leader')
            ->where('id', '!=', $currentUser->id)
            ->with('userDetails')
            ->get();
        $otherLeadersData = $otherLeaders->map(function ($leader) {
            $name = $leader->firstName . ' ' . $leader->lastName;
            $leaders = Leader::where('leadersId', $leader->id)->first();
            $leaderCore = LeaderCoreParty::where('leaderId', $leader->id)->with('party')->first();
            $leaderMinistry = $leaders->leaderElectedRole ?? null;

            return [
                'leaderId' => $leader->id,
                'name' => $name,
                'profilePicture' => $leader->userDetails && $leader->userDetails->profileImage !== null
                    ? $leader->userDetails->profileImage
                    : null,
                'leaderMinistry' => $leaderMinistry,
                'partyName' => !empty($leaderCore) ? $leaderCore->party->name : null
            ];
        });
        return response()->json(['status' => 'success', 'message' => 'Leader Profile details', 'result' => $otherLeadersData], 200);

    }

    /**
     * @OA\Post(
     *     path="/api/compareLeaders",
     *     summary="Competetive Analysis",
     *     tags={"Competetive Analysis"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="leaderId",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="leaderId", type="string", format="leaderId", example="leaderId"),
     *                 ),
     *                 example={
     *                     {
     *                         "leaderId": "leaderId3"
     *                     },
     *                     {
     *                         "leaderId": "leaderId2"
     *                     }
     *                 },
     *                 description="Array of leader IDs"
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

    public function compareLeaders(Request $request)
    {
        try {
            $leadersId = $request->leaderId;
            $leadersDetails = [];
            foreach ($leadersId as $leaderId) {
                $comparision = [
                    'leaderId' => $leaderId['leaderId'],
                    'profileImage' => null,
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
                $leaderdetails = LeaderDetails::where('leadersId', $leaderId['leaderId'])->with('user')->first();
                $leaders = Leader::where('leadersId', $leaderId['leaderId'])->with('user')->first();
                $userDetails = UserDetails::where('userId', $leaderId['leaderId'])->first();
                $leaderFollowerIds = LeaderFollowers::where('leaderId', $leaderId)->pluck('followerId');
                $youngAgeCount = 0;
                $middleAgeCount = 0;
                $maleCount = 0;
                $femaleCount=0;
                foreach ($leaderFollowerIds as $followerId) {
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
                    }
                    $gender = strtoupper($user->gender);
                    if ($gender == 'MALE') {
                        $maleCount++;
                    } elseif ($gender == 'FEMALE') {
                        $femaleCount++;
                    }
                }
                if ($leaderdetails != '') {
                    $postFrequency = Calculation::calculatePostFrequency($leaderId['leaderId']);
                    $followersCount = $leaders->followercount;
                    $followerCounts = count( $leaderFollowerIds);

                    $profilePicture = $userDetails->profileImage;
                    $comparision = [
                        'leaderId' => $leaderId['leaderId'],
                        'profileImage' => $profilePicture,
                        'followersCount' => $followerCounts,
                        'youngUsers' => $youngAgeCount,
                        'middledAgeUsers' =>    $middleAgeCount,
                        'maleFollowers' =>  $maleCount,
                        'femaleFollowers' => $femaleCount,
                        'transgenderFollowers' => $leaderdetails->transgenderFollowers,
                        'appreciatePostCount' => $leaderdetails->appreciatePostCount,
                        'likePostCount' => $leaderdetails->likePostCount,
                        'carePostCount' => $leaderdetails->carePostCount,
                        'unlikesPostCount' => $leaderdetails->unlikesPostCount,
                        'sadPostCount' => $leaderdetails->sadPostCount,
                        'issuedResolvedCount' => $leaderdetails->issuedResolvedCount,
                        'postFrequency' => $postFrequency,
                        'sentiments' => $leaderdetails->sentiments,
                        'responseTime' => $leaderdetails->responseTime
                    ];
                    $leadersDetails[] = $comparision;
                }

            }
            return response()->json(['status' => 'success', 'message' => "Leader Competetive Analysis", 'result' => $leadersDetails], 200);

        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/generateAnalysisReport",
     *     summary="Generate Analysis Report",
     *     tags={"Competetive Analysis"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="leaderId",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="leaderId", type="string", format="leaderId", example="leaderId"),
     *                 ),
     *                 example={
     *                     {
     *                         "leaderId": "leaderId3"
     *                     },
     *                     {
     *                         "leaderId": "leaderId2"
     *                     }
     *                 },
     *                 description="Array of leader IDs"
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
    public function generateAnalysisReport(Request $request)
    {
        try {
            $leadersId = $request->leaderId;
            $leadersDetails = [];

        foreach ($leadersId as $leaderId) {
                $comparision = [
                    'leaderId' => $leaderId['leaderId'],
                    'leaderName' => null,
                    'leaderParty' => null,
                    'profileImage' => null,
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
                $leaderdetails = LeaderDetails::where('leadersId', $leaderId['leaderId'])->with('user')->first();
                $leaders = Leader::where('leadersId', $leaderId['leaderId'])->with('user')->first();
                $leadersparty_id = $leaders->leaderParyId;
                $party_name = Party::where('id', $leadersparty_id)->first();
                $leader_party_name = ($party_name) ? $party_name->name : null;
                $userDetails = UserDetails::where('userId', $leaderId['leaderId'])->first();
                $leaderFollowerIds = LeaderFollowers::where('leaderId', $leaderId)->pluck('followerId');
                $youngAgeCount = 0;
                $middleAgeCount = 0;
                $maleCount = 0;
                $femaleCount=0;
                foreach ($leaderFollowerIds as $followerId) {
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
                    }
                    $gender = strtoupper($user->gender);
                    if ($gender == 'MALE') {
                        $maleCount++;
                    } elseif ($gender == 'FEMALE') {
                        $femaleCount++;
                    }
                }
                if ($leaderdetails != '') {
                    $postFrequency = Calculation::calculatePostFrequency($leaderId['leaderId']);
                    $followersCount = $leaders->followercount;
                    $followerCounts = count( $leaderFollowerIds);

                    $profilePicture = $userDetails->profileImage;
                    $leaderFullName = $leaders->user->firstName." ".$leaders->user->lastName;
                    $comparision = [
                        'leaderId' => $leaderId['leaderId'],
                        'leaderName' => $leaderFullName,
                        'leaderParty' => $leader_party_name,
                        'profileImage' => $profilePicture,
                        'followersCount' => $followerCounts,
                        'youngUsers' => $youngAgeCount,
                        'middledAgeUsers' =>    $middleAgeCount,
                        'maleFollowers' =>  $maleCount,
                        'femaleFollowers' => $femaleCount,
                        'transgenderFollowers' => $leaderdetails->transgenderFollowers,
                        'appreciatePostCount' => $leaderdetails->appreciatePostCount,
                        'likePostCount' => $leaderdetails->likePostCount,
                        'carePostCount' => $leaderdetails->carePostCount,
                        'unlikesPostCount' => $leaderdetails->unlikesPostCount,
                        'sadPostCount' => $leaderdetails->sadPostCount,
                        'issuedResolvedCount' => $leaderdetails->issuedResolvedCount,
                        'postFrequency' => $postFrequency,
                        'sentiments' => $leaderdetails->sentiments,
                        'responseTime' => $leaderdetails->responseTime
                    ];
                    $leadersDetails[] = $comparision;
                }

            }

            // (new FastExcel($leadersDetails))->export('report.csv');
            // $url = env('APP_URL') . '/report.csv';
            $pdf = PDF::loadView('reports.reports', ['leadersDetails' => $leadersDetails]);
            $fileName = 'report-'.mt_rand(1000000000, 9999999999) . '.pdf';
            $filePath = 'reports/'.$fileName; 
            $pdf->setPaper('a2', 'landscape');


            // Since s3 not configured in this Project, using local storage for now

            // // Save the PDF to S3
            // Storage::disk('s3')->put($filePath, $pdf->output());
            // // Get the URL of the saved PDF
            // $url = Storage::disk('s3')->url($filePath);


            // Save the PDF to the public directory
            if (!is_dir(public_path('reports'))) {
                // Create the reports directory if it doesn't exist
                mkdir(public_path('reports'), 0777, true);
            }

            if ($pdf->save(public_path($filePath))) {
                $url = env('APP_URL') . '/' . $filePath;
                return response()->json(['status' => 'success', 'message' => "File Generated", 'url' => $url], 200);
            } else {
                throw new Exception("Failed to generate the PDF");
            }
        } catch (\Exception $e) {
            // return $e->getMessage().' - '.$e->getLine();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
        }
    }

    public function destroy(string $id)
    {
        //
    }
}
