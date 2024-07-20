<?php

namespace App\Http\Controllers\PartyController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\AssemblyConsituency;
use App\Models\CorePartyChangeRequest;
use App\Models\Leader;
use App\Models\LeaderCoreParty;
use App\Models\LokSabhaConsituency;
use App\Models\Party;
use App\Models\PartyLogin;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaderManagementonPartyPages extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/leaderPartyManagement",
     *     summary="Fetch all Leader who have Access",
     *     tags={"PartyProfile"},
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
            $perPage = 10;
            $keyword = request('keyword');
            $currentPage = request('page', 1);
            $partyId = request('partyId');
            $party = Party::find($partyId);
            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }

            $leaderDetails = LeaderCoreParty::where('corePartyId', $partyId)->get();
            $userIds = $leaderDetails->pluck('leaderId');
            $leader = User::whereIn('id', $userIds)
                ->with('userDetails', 'leaderDetails.leaderMinistry')
                ->get()
                ->map(function ($user) {
                    $leaderConsituencyId = !empty($user->leaderDetails) ? $user->leaderDetails->leaderConsituencyId ?? '' : null;
                    $leaderConsituency='';
                    if(  $leaderConsituencyId!='')
                    {
                        $consituency = AssemblyConsituency::find( $leaderConsituencyId);
                        $leaderConsituency = $consituency != '' ? $consituency->name : (AssemblyConsituency::find( $leaderConsituencyId) != '' ? LokSabhaConsituency::find( $leaderConsituencyId)->name : null);

                    }
                    $coreParty = LeaderCoreParty::where('leaderId', $user->id)->with('party')->first();
                    $partyName = isset($coreParty->party) ? ($coreParty->party->name ?? null) : null;
                    $leaderConsituency;
                    return [
                        'leaderId' => $user->id,
                        'name' => $user->getFullName(),
                        'profileImage' => $user->userDetails->profileImage,
                        'leaderMinistry' => $user->leaderDetails->leaderElectedRole ?? null,
                        'leaderConsituency' => $leaderConsituency,
                        'leaderCoreParty' => $partyName,

                    ];
                });
            if (!empty($keyword)) {
                $leader = $leader->filter(function ($user) use ($keyword) {
                    return stripos($user['name'], $keyword) !== false;
                });
            }
            $leader = collect($leader);
            $paginatedResult = $leader->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $leaderPaginator = new LengthAwarePaginator(
                $paginatedResult,
                $leader->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
            return $leaderPaginator;
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/addNewLeader",
     *     summary="Fetch all leader to send them request to join party",
     *     tags={"PartyProfile"},
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
    public function addNewLeader()
    {
        try {
            $partyId = request('partyId');
            $perPage = 10;
            $keyword = request('keyword');
            $currentPage = request('page', 1);

            $party = Party::find($partyId);
            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }
            $leaderDetails = LeaderCoreParty::where('corePartyId', $partyId)->get();
            $userIds = $leaderDetails->pluck('leaderId');
            $leader = User::whereNotIn('id', $userIds)
                ->role('Leader')
                ->with('userDetails', 'leaderDetails.leaderMinistry')
                ->get()
                ->map(function ($user) {
                    $leaderConsituency = '';
                    $consituency = AssemblyConsituency::find($user->leaderConsituencyId);

                    $leaderConsituency = $consituency != '' ? $consituency->name : (LokSabhaConsituency::find($user->leaderConsituencyId) != '' ? LokSabhaConsituency::find($user->leaderConsituencyId)->name : null);
                    $coreParty = LeaderCoreParty::where('leaderId', $user->id)->with('party')->first();
                    $partyName = isset($coreParty->party) ? ($coreParty->party->name ?? null) : null;
                    $leaderConsituency;
                    return [
                        'leaderId' => $user->id,
                        'name' => $user->getFullName(),
                        'leaderMinistry' => $user->leaderMinistry,
                        'leaderConsituency' => $leaderConsituency,
                        'leaderCoreParty' => $partyName,

                    ];
                });

            if (!empty($keyword)) {
                $leader = $leader->filter(function ($user) use ($keyword) {
                    return stripos($user['name'], $keyword) !== false;
                });
            }
            $leader = collect($leader);
            $paginatedResult = $leader->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $leaderPaginator = new LengthAwarePaginator(
                $paginatedResult,
                $leader->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );

            return $leaderPaginator;
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Show the form for creating a new resource.
     */
    /**
     * @OA\Get(
     *     path="/api/requestByLeader",
     *     summary="Fetch all request party for request",
     *     tags={"PartyProfile"},
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
    public function requestByLeader()
    {
        try {
            $partyId = request('partyId');
            $perPage = 10;
            $keyword = request('keyword');
            $currentPage = request('page', 1);

            $party = Party::find($partyId);
            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }
            $partyRequest = CorePartyChangeRequest::where('partyId', $partyId)->get();
            $mappedParties = collect($partyRequest)->map(function ($partyRequestToJoin) {
                $user = User::where('id', $partyRequestToJoin->leaderId)->with('userDetails')->first();
                $leaderParty = LeaderCoreParty::where('leaderId', $partyRequestToJoin->userId)->with('party')->first();

                return [

                    'leaderId' => $partyRequestToJoin->leaderId,
                    'name' => $user->getFullName(),
                    'profileImage' => $user->userDetails->profileImage,
                    'ministry' => $partyRequestToJoin->ministry,
                    'currentParty' => $leaderParty->party->name ?? null,

                ];

            });

            if (!empty($keyword)) {
                $leader = $mappedParties->filter(function ($user) use ($keyword) {
                    return stripos($user['name'], $keyword) !== false;
                });
            }
            $leader = collect($mappedParties);
            $paginatedResult = $leader->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $leaderPaginator = new LengthAwarePaginator(
                $paginatedResult,
                $leader->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath()]
            );
            return $leaderPaginator;
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Post(
     *   path="/api/acceptRequest/{id}",
     *   summary="Update role by id",
     *   tags={"PartyProfile"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Enter details",
     *     @OA\JsonContent(
     *       required={"centralPartyRole","statePartyRole","deparment","additionalComments"},
     *       @OA\Property(property="centralPartyRole", type="string", example="centralPartyRole"),
     *       @OA\Property(property="statePartyRole", type="string", example="statePartyRole"),
     *       @OA\Property(property="deparment", type="string", example="deparment"),
     *       @OA\Property(property="additionalComments", type="string", example="additionalComments"),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Error while updating"
     *   ),
     *   security={{ "apiAuth": {} }}
     * )
     */

    public function acceptRequest(Request $request, $id)
    {
        try {
            $partyId = request('partyId');
            $centralPartyRole = $request->centralPartyRole;
            $statePartyRole = $request->statePartyRole;
            $deparment = $request->deparment;
            $additionalComments = $request->additionalComments;
            $party = Party::find($partyId);
            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }
            $coreParty = [
                'leaderId' => $id,
                'corePartyId' => $partyId,
                'centralPartyRole' => $centralPartyRole,
                'additionalComments' => $additionalComments,
                'statePartyRole' => $statePartyRole,
                'deparment' => $deparment,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id
            ];
            LeaderCoreParty::create($coreParty);
            CorePartyChangeRequest::where('leaderId', $id)->delete();
            return response()->json(['status' => 'success', 'message' => "Leader Accepted to join"], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/removeLeader/{id}",
     *     summary="Delete leader party access",
     *     tags={"PartyProfile"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
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
    public function removeLeader($id)
    {
        try {
            $partyId = request('partyId');
            $party = Party::find($partyId);
            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }
            LeaderCoreParty::where('corePartyId', $partyId)->where('leaderId', $id)->delete();
            return response()->json(['status' => 'success', 'message' => "Leader remove from party"], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


    /**
     * @OA\Put(
     *   path="/api/updateLeader/{id}",
     *   summary="Update leader by id",
     *   tags={"PartyProfile"},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Enter details",
     *     @OA\JsonContent(
     *       required={"centralPartyRole","statePartyRole","deparment","additionalComments"},
     *       @OA\Property(property="centralPartyRole", type="string", example="centralPartyRole"),
     *       @OA\Property(property="statePartyRole", type="string", example="statePartyRole"),
     *       @OA\Property(property="deparment", type="string", example="deparment"),
     *       @OA\Property(property="additionalComments", type="string", example="additionalComments"),
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Error while updating"
     *   ),
     *   security={{ "apiAuth": {} }}
     * )
     */

    public function updateLeader($id, Request $request)
    {
        try {
            $partyId = request('partyId');
            $centralPartyRole = $request->centralPartyRole;
            $statePartyRole = $request->statePartyRole;
            $deparment = $request->deparment;
            $additionalComments = $request->additionalComments;
            $status = $request->status;

            $party = Party::find($partyId);

            if (empty($party)) {
                return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
            }

            if($status!='' && $status=='rejected')
            {
                CorePartyChangeRequest::where('leaderId', $id)->where('partyId',$partyId)->delete();
                return response()->json(['status' => 'error', 'message' => "Request Rejected"], 404);
            }
            $coreParty = [
                'leaderId' => $id,
                'corePartyId' => $partyId,
                'centralPartyRole' => $centralPartyRole,
                'additionalComments' => $additionalComments,
                'statePartyRole' => $statePartyRole,
                'deparment' => $deparment,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id
            ];

            $existingRecord = LeaderCoreParty::where('leaderId', $id)
                ->where('corePartyId', $partyId)
                ->first();

            if ($existingRecord) {
                $existingRecord->update($coreParty);
            } else {
                LeaderCoreParty::create($coreParty);
            }

            return response()->json(['status' => 'success', 'message' => "Leader updated successfully"], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/getLeaderDetailsById/{id}",
     *     summary="Fetch getLeaderDetails By Id",
     *     tags={"PartyProfile"},
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
    public function getLeaderDetailsById($id)
    {
        $partyId = request('partyId');
        $party = Party::where('id', $partyId)->first();

        if (empty($party)) {
            return response()->json(['status' => 'error', 'message' => "Party not found"], 404);
        }

        $leaders = LeaderCoreParty::where('leaderId', $id)
            ->where('corePartyId', $partyId)
            ->first();

        $coreParty = [];


        $leaderParty = [
            'leaderId' => $id,
            'corePartyId' => $partyId,
            'centralPartyRole' => $leaders->centralPartyRole ?? null,
            'additionalComments' => $leaders->additionalComments ?? null,
            'statePartyRole' => $leaders->statePartyRole ?? null,
            'deparment' => $leaders->deparment ?? null,
        ];

        $leader = User::where('id', $id)
            ->with('userDetails', 'leaderDetails.leaderMinistry')
            ->first();

        $consituency = AssemblyConsituency::find($leader->leaderDetails->leaderConsituencyId);
        $leaderConsituency = $consituency != '' ? $consituency->name : (LokSabhaConsituency::find($leader->leaderDetails->leaderConsituencyId) != '' ? LokSabhaConsituency::find($leader->leaderDetails->leaderConsituencyId)->name : null);
        $coreParty = LeaderCoreParty::where('leaderId', $leader->id)->with('party')->first();
        $partyName = isset($coreParty->party) ? ($coreParty->party->name ?? null) : null;
        $leaderDetails = [
            'leaderId' => $leader->id,
            'name' => $leader->getFullName(),
            'profileImage' => $leader->userDetails->profileImage,
            'leaderMinistry' => $leader->leaderElectedRole,
            'leaderConsituency' => $leaderConsituency,
            'leaderCoreParty' => $partyName,

        ];
        return response()->json(['status' => 'success', 'message' => 'LeaderDetails', 'aboutLeader' => $leaderDetails, 'aboutLeaderParty' => $leaderParty], 200);
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
