<?php

namespace App\Http\Controllers\ManifestoTracker;

use App\Helpers\Action;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\AssemblyElectionHistory;
use App\Models\ElectionHistory;
use App\Models\ElectionHistoryCoorectionRequest;
use App\Models\ElectionType;
use App\Models\LokSabhaElectionHistory;
use App\Models\Party;
use App\Models\UpcomingElection;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ElectionHistoryDetailsController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/upcomingElection",
     *     summary="upcomingElection",
     *     tags={"Election History Details"},
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
    public function upcomingElection(Request $request)
    {
        $currentDate = Carbon::now(); 
        $upcomingElections = UpcomingElection::where('electionDate', '>=', $currentDate->format('Y-m-d'))->get();
        // return $upcomingElections;
        $searchKeyword = $request->get('searchKeyword');
        $upcomingElections =    ElectionType::leftJoin('election_histories', 'election_histories.electionTypeId', 'election_types.id')
                                ->leftJoin('states','states.id','election_types.stateId')
                                ->where('electionStatus', 'Pending')
                                ->where('election_types.status','Active')
                                ->select('election_types.id as upcominElectionId',
                                        'election_histories.electionHistoryYear as year',
                                        'election_types.electionName as electionType',
                                        DB::raw("COALESCE(states.name, 'All') AS state"));
        if ($searchKeyword) {
            $upcomingElections->where(function ($query) use ($searchKeyword) {
                $query->where('election_types.electionName', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('states.name', 'like', '%' . $searchKeyword . '%')
                    ->orWhere('election_histories.electionHistoryYear', 'like', '%' . $searchKeyword . '%');
            });
        }     
        $upcomingElections = $upcomingElections->get();
        return response()->json(['status' => 'success', 'message' => 'Upcoming Elections', 'result' =>$upcomingElections], 200);
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
     *     path="/api/electionhistoryDetails",
     *     summary="Search Election Details",
     *     tags={"Election History Details"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"electionType","electionYear","stateId"},
     *         @OA\Property(property="electionType", type="string", example="LokSabha"),
     *         @OA\Property(property="electionYear", type="string", example="2019"),
     *         @OA\Property(property="stateId", type="string", example="22ttdtd"),
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
        try {
            $electionType = $request->input('electionType');
            $stateId = $request->input('stateId');
            $electionYear = $request->input('electionYear');
            $searchKeyword = $request->input('searchKeyword');
            $electionTypesQuery = ElectionType::leftJoin('election_histories', 'election_histories.electionTypeId', '=', 'election_types.id')
                                ->leftJoin('parties', 'parties.id', 'election_histories.rulingParty')
                                ->where('election_types.electionName', $electionType)
                                // ->where('election_histories.electionHistoryYear', $electionYear)
                                ->select(
                                    'election_types.id as election_type_id',
                                    'election_types.electionTypeDescriptionBrief as electionHeader',
                                    'election_types.electionNumberOfSeats as totalSeats',
                                    'election_types.total_seats_won as total_seats_won',
                                    'parties.logo as singleLargestParty',
                                    'election_histories.electionHistoryYear as electionhistoryYear',
                                    'parties.logo as winningParty',
                                    'parties.name as partyname',
                                    'election_types.electionNumberOfSeats as seatsWon'
                                );

            if ($electionType == "LokSabha") {
                $electionTypesQuery->leftJoin('lok_sabha_election_histories', 'lok_sabha_election_histories.electionHistoryId', '=', 'election_histories.id')
                    ->addSelect('lok_sabha_election_histories.percentageinParliament', 'lok_sabha_election_histories.turnout', 'lok_sabha_election_histories.majority', 
                    'lok_sabha_election_histories.turnout as governmentSeats', 'lok_sabha_election_histories.votesPercentage')
                    ->where('election_types.electionStatus', '=', 'Completed');
            } elseif ($electionType == "Assembly") {
                $electionTypesQuery->leftJoin('assembly_election_histories', 'assembly_election_histories.electionHistoryId', '=', 'election_histories.id')
                    ->where('election_types.stateId', '=', $stateId)
                    ->where('election_types.electionStatus', '=', 'Completed')
                    ->addSelect('assembly_election_histories.percentageinParliament', 'assembly_election_histories.turnout', 'assembly_election_histories.majority',
                    'assembly_election_histories.turnout as governmentSeats', 'assembly_election_histories.votesPercentage');
            }


            $allLokSabhaHistory = $electionTypesQuery->orderBy('election_histories.electionHistoryYear')->get();
            $electionTypes = $electionTypesQuery->where('election_histories.electionHistoryYear', $electionYear)->first();

            $electionTypes->allLokSabhaHistory = $allLokSabhaHistory;

            if (!$electionTypes) {
                return response()->json(['status' => 'error', 'message' => 'Election History not found'], 404);
            }

            return response()->json(['status' => 'success', 'message' => 'Election History Details', 'result' => $electionTypes], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'An error occurred'], 500);
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

    public function search(Request $request)
    {
          $searchType = $request->searchType;
          $keyword = $request->keyword;
            if($searchType == "electionHistory"){
  
            }
    }
    /**
     * @OA\Post(
     *     path="/api/correctionRequest",
     *     summary="Request For Correction",
     *     tags={"Election History Details"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"correctionTitle","correctionDescription","media"},
     *         @OA\Property(property="correctionTitle", type="string", example="correctionTitle"),
     *         @OA\Property(property="correctionDescription", type="string", example="correctionDescription"),
     *         @OA\Property(property="election_type_id", type="string", example="9a7e554a-4ca5-454b-85e4-2f746e055745"),
     *         @OA\Property(property="media", type="string", example="file"),
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
 
    public function correctionRequest(Request $request)
    {
        $partyId = request('partyId');
        $correctionTitle = $request->get('correctionTitle');
        $correctionDescription = $request->get('correctionDescription');
        $correctionMedia = $request->get('media');
        $election_type_id = $request->get('election_type_id');
        $userid = Auth::user()->id;
        $selectedUserId = !empty($partyId) ? $partyId : $userid;
        $userType = !empty($partyId) ? 'Party' : Auth::user()->getRoleNames()[0];
    
        $existingRequest = ElectionHistoryCoorectionRequest::where([
            'correctionTitle' => $correctionTitle,
            'descriptions' => $correctionDescription,
            'media' => $correctionMedia,
            'userId' => $selectedUserId,
            "electionTypeId" => $election_type_id,
        ])->latest()->first();
            
        if (!$existingRequest) {
            $this->createCorrectionRequest($correctionTitle, $correctionDescription, $correctionMedia, $election_type_id, $selectedUserId);
            $this->sendCorrectionRequestEmail($selectedUserId, $userType);
            return response()->json(['status' => 'success', 'message' => 'Correction request created'], 200);
        } else {
            $status = $existingRequest->status;
            $createdBy = $existingRequest->createdAt;
            $currentDate = now();
            $daysDifference = $createdBy->diffInDays($currentDate);
            if (($status === "Pending" || $status === "Unresolved") && $daysDifference > 30 || $existingRequest->status === "Resolved") {
                $this->createCorrectionRequest($correctionTitle, $correctionDescription, $correctionMedia, $election_type_id, $selectedUserId);
                $this->sendCorrectionRequestEmail($selectedUserId, $userType);
                return response()->json(['status' => 'success', 'message' => 'Correction request created'], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Correction request already exists or is unresolved'], 400);
            }
        }
    }

    private function createCorrectionRequest($title, $description, $media, $electionTypeId, $userId)
    {
        $correctionDetails = [
            "correctionTitle" => $title,
            "descriptions" => $description,
            "media" => $media,
            "electionTypeId" => $electionTypeId,
            "status" => "Pending",
            'userId' => $userId,
            "createdBy" => Auth::user()->id,
            "updatedBy" => Auth::user()->id,
        ];
        ElectionHistoryCoorectionRequest::create($correctionDetails);
    }

    private function sendCorrectionRequestEmail($userId, $userType)
    {
        $template = Action::getTemplate('request correction');
        $adminUser = User::role('Super Admin')->first();
        $fullName = $adminUser->getFullName();
        $reportedUser = ($userType == 'Party') ? Party::find($userId) : User::find($userId);
        $reportedUserName = ($userType == 'Party') ? $reportedUser->name : $reportedUser->getFullName();
        if ($template['type'] == "template") {
            foreach ($template['data'] as $d) {
                $content = str_replace(["{adminName}", "{requestCorrectionBy}"], [$fullName, $reportedUserName], $d->content);
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

}