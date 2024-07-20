<?php

namespace App\Http\Controllers\Consituency;

use App\Helpers\FetchAllPost;
use App\Http\Controllers\Controller;
use App\Models\AssemblyConsituency;
use App\Models\ConsituencyDetails;
use App\Models\ConsituencyExplore;
use App\Models\ConsituencyPoliticalTimeLine;
use App\Models\LeaderMinistry;
use App\Models\LokSabhaConsituency;
use App\Models\State;
use App\Models\UserFollowerTag;
use DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Auth;

class ConsituencyController extends Controller
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
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/consituency/{id}",
     *     summary="View Consituency Profile",
     *     tags={"Consituency Management"},
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
        $perPage = env('PAGINATION_PER_PAGE', 300);
        $currentPage = request('page', 1);
        $consituencyId = $id;
        $partyId = request('partyId');
        $results = LokSabhaConsituency::with('stateDetails')->find($consituencyId);
        
        if ($results == '') {
            $results = AssemblyConsituency::with('stateDetails')->find($consituencyId);
        }

        $consituencyDetails = ConsituencyDetails::where('consituencyId', $consituencyId)->first();
        $state = '';
        if ($results->stateDetails !== null) {
            $stateId = $results->stateDetails->stateId;
            $state = State::where('id', $stateId)->first();
    }

        $isFollowing = UserFollowerTag::where('followedTags', $results->name)->where('userId', Auth::user()->id)->exists();
        $exploreData = ConsituencyExplore::where("consituencyId", $consituencyId)->get();
        $exploreResult = $exploreData->map(function ($explore) {
            return [
                'id' => $explore->id,
                'title' => $explore->exploreName,
                'backgroundImage' => $explore->backgroundImage,
            ];
        });
        $desiredTotal = $exploreResult->count();
        $pagedPosts = $exploreResult->forPage($currentPage, $perPage)->values();

        $exploreResult = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        $lokSabhaExist = LokSabhaConsituency::where('id', $results->id)->exists();
        if ($lokSabhaExist == true) {
            $consituencyType = "LokSabha";
        } else {
            $consituencyType = "Assembly";
        }
       $followerCount = UserFollowerTag::where('followedTags', $results->name)->where('userId', Auth::user()->id)->count();
        $mainData = [
            'consituencyType' => $consituencyType,
            'consituencyName' => $results->name,
            'descriptionBrief' => $results->descriptionBrief,
            'consituencyNumber' => $results->code,
            'followerCount' =>  $followerCount ,
            'volunteersCount' => $results->volunteerscount,
            'stateName' => $state ? $state->name : null,
            'isFollowing' => $isFollowing,
            'descriptions' => $results->descriptionShort,
            'backgroundImage' => $consituencyDetails->backgrouundImage ?? null,
        ];
        $about = [
            'population' => $results->population,
            'area' => $consituencyDetails->area ?? null,
            'totalElectors' => $results->populationElectors,
            'populationElectorsMale' => $results->populationElectorsMale,
            'populationElectorsFemale' => $results->populationElectorsFemale,
            'developmentIndex' => $consituencyDetails->developmentIndex ?? null,
            'safetyIndex' => $consituencyDetails->safetyIndex ?? null,
            'literacyIndex' => $consituencyDetails->literacyIndex ?? null,
            'corruptionIndex' => $consituencyDetails->corruptionIndex ?? null,
        ];
        $consituencyTimeLine = ConsituencyPoliticalTimeLine::where('consituencyId', $consituencyId)->with('user', 'userDetails', 'leader')->get();
        $consituencyTimeLineArray = $consituencyTimeLine->map(function ($timeline) {
            $name = $timeline->user->firstName . " " . $timeline->user->lastName;
            $ministry = LeaderMinistry::where('leaderId', $timeline->user->id)->first();
            return [
                'id' => $timeline->id,
                'name' => $name,
                'ministryStatus' => $ministry ? $ministry->status : null,
                'leaderMinistry' => $timeline->leaderElectedRole,
                'profileImage' => $timeline->userDetails->profileImage,
                'inPowerDate' => $timeline->inPowerDate
            ];
        });
        $stream = FetchAllPost::getAllPost($currentPage, $partyId, $results->name, null, null, null, null);
        $history = $consituencyDetails->history ?? null;
        return response()->json(['status' => 'success', 'message' => 'Consituency Details', 'mainData' => $mainData, 'about' => $about, 'consituencyTimeLine' => $consituencyTimeLineArray, 'history' => $history, 'stream' => $stream, 'explore' => $exploreResult], 200);

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
