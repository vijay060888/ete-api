<?php

namespace App\Http\Controllers\PostController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\PollsByCitizenDetails;
use App\Models\PollsByCitizenVote;
use App\Models\PollsByLeaderDetails;
use App\Models\PollsByLeaderVote;
use App\Models\PollsByPartyDetails;
use App\Models\PollsByPartyVote;
use App\Models\PostByCitizen;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use Auth;
use Illuminate\Http\Request;

class PollsManagementController extends Controller
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
     *     path="/api/polls",
     *     summary="Add your Polls ",
     *     tags={"Polls Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"postId", "selectedOption", "authorType"},
     *             @OA\Property(property="postId", type="string", example="postId"),
     *             @OA\Property(property="selectedOption", type="string", example="option1"),
     *             @OA\Property(property="authorType", type="string", example="leader/party"),
     *         ),
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
     *         description="Unauthorized "
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found "
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     **/
    public function store(Request $request)
    {
        try {
            $postId = $request->postId;
            $selectedOption = $request->selectedOption;
            $pollsByType = $request->authorType;
            $partyId = $request->partyId;
            $userid = empty($partyId) ? Auth::user()->id : $partyId;

            // $postByModelClass = ($pollsByType == 'Leader') ? PostByLeader::class :
            //     (($pollsByType == 'Citizen') ? PostByCitizen::class :
            //         (($pollsByType == 'Party') ? PostByParty::class : null));

            $pollsByDetailsModelClass = ($pollsByType == 'Leader') ? PollsByLeaderDetails::class :
                (($pollsByType == 'Citizen') ? PollsByCitizenDetails::class :
                    (($pollsByType == 'Party') ? PollsByPartyDetails::class : null));

            $pollsByVoteModelClass = ($pollsByType == 'Leader') ? PollsByLeaderVote::class :
                (($pollsByType == 'Citizen') ? PollsByCitizenVote::class :
                    (($pollsByType == 'Party') ? PollsByPartyVote::class : null));

            $postByVariable = ($pollsByType == 'Leader') ? "postByLeaderId" :
                (($pollsByType == 'Citizen') ? 'postByCitizenId' :
                    (($pollsByType == 'Party') ? 'postByPartyId' : null));


            $vote = $pollsByVoteModelClass::where([
                "$postByVariable" => $postId,
                'userId' => $userid,
            ])->first();
            if (!$vote) {
                $pollsDetails = $pollsByDetailsModelClass::where("$postByVariable", $postId)->where("pollOption", $selectedOption)->first();
                $pollsDetails->optionCount++;
                $pollsDetails->save();
            }
            $pollsByVoteModelClass::updateOrInsert(
                [
                    "$postByVariable" => $postId,
                    'userId' => $userid,
                ],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    "$postByVariable" => $postId,
                    "userId" => $userid,
                    "selectedOption" => $selectedOption,
                    "createdBy" => Auth::user()->id,
                    "updatedBy" => Auth::user()->id,
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]
            );
            return response()->json(['status' => 'success', 'message' => 'Polls Submitted',], 200);
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

    }

    /**
     * Remove the specified resource from storage.
     */
  /**
 * @OA\Delete(
 *     path="/api/polls/{postId}",
 *     summary="Remove your voting",
 *     tags={"Polls Management"},
 *     @OA\Parameter(
 *         name="postId",
 *         in="path",
 *         required=true,
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="authorType",
 *                 type="string",
 *                 description="Valid AuthorType is required"
 *             ),
 *           
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
 *         response=400,
 *         description="Bad Request",
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Data not found",
 *     ),
 *     security={{ "apiAuth": {} }}
 * )
 */

    public function destroy(string $id, Request $request)
    {
        try{
        $pollsByType = $request->authorType;
        $userid = Auth::user()->id;
        $postId = $id;

        $pollsByDetailsModelClass = ($pollsByType == 'Leader') ? PollsByLeaderDetails::class :
            (($pollsByType == 'Citizen') ? PollsByCitizenDetails::class :
                (($pollsByType == 'Party') ? PollsByPartyDetails::class : null));

        $pollsByVoteModelClass = ($pollsByType == 'Leader') ? PollsByLeaderVote::class :
            (($pollsByType == 'Citizen') ? PollsByCitizenVote::class :
                (($pollsByType == 'Party') ? PollsByPartyVote::class : null));

        $postByVariable = ($pollsByType == 'Leader') ? "postByLeaderId" :
            (($pollsByType == 'Citizen') ? 'postByPartyId' :
                (($pollsByType == 'Party') ? 'postByCitizenId' : null));

        $getOption = $pollsByVoteModelClass::where("$postByVariable",$postId)->where('userId',$userid)->first();
        $selectedOption=$getOption->selectedOption;
        $pollsDetails = $pollsByDetailsModelClass::where("$postByVariable", $postId)->where("pollOption", $selectedOption)->first();
        $pollsDetails->optionCount--;
        $pollsDetails->save();
        $getOption->delete();

        return response()->json(['status' => 'success', 'message' => 'Polls deleted successfully',], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
    }
    }
}