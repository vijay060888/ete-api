<?php

namespace App\Http\Controllers\PostController;

use App\Http\Controllers\Controller;
use App\Models\AssemblyConsituency;
use App\Helpers\LogActivity;
use App\Models\LokSabhaConsituency;
use App\Models\Party;
use App\Models\User;
use Illuminate\Http\Request;
use DB;
class MentionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\POST(
     *     path="/api/searchMention",
     *     summary="Search Mention",
     *     tags={"Search"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"keyword"},
     *         @OA\Property(property="keyword", type="string", example="keyword"),
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
    public function searchMention(Request $request)
    {
        try {
            $perPage = env('PAGINATION_PER_PAGE', 10);

            $keyword = $request->keyword;

            $partyResults = Party::select('name', 'name as displayName')
                ->where('name', 'ILIKE', "$keyword%")
                ->orWhere('name', 'ILIKE', "%$keyword%")
                ->orderByRaw('CASE WHEN "name" ILIKE ? THEN 0 ELSE 1 END, "name"', ["$keyword%"])
                ->limit(10)
                ->get();

            $assemblyResults = AssemblyConsituency::select('name', 'name as displayName')
                ->where('name', 'ILIKE', "$keyword%")
                ->orWhere('name', 'ILIKE', "%$keyword%")
                ->orderByRaw('CASE WHEN "name" ILIKE ? THEN 0 ELSE 1 END', ["$keyword%"])
                ->limit(10)
                ->get();

            $lokSabhaResults = LokSabhaConsituency::select('name', 'name as displayName')
                ->where('name', 'ILIKE', "$keyword%")
                ->orWhere('name', 'ILIKE', "%$keyword%")
                ->orderByRaw('CASE WHEN "name" ILIKE ? THEN 0 ELSE 1 END', ["$keyword%"])
                ->limit(10)
                ->get();

            $userResults = User::selectRaw('*, CASE WHEN "firstName" ILIKE ? THEN 0 ELSE 1 END AS order_column', ["$keyword%"])
            ->whereHas('roles', function ($query) {
                $query->where('name', 'Leader');
            })
            ->where(function ($query) use ($keyword) {
                $query->orWhere('firstName', 'ILIKE', "$keyword%")
                    ->orWhere('lastName', 'ILIKE', "$keyword%")
                    ->orWhere('firstName', 'ILIKE', "%$keyword%")
                    ->orWhere('lastName', 'ILIKE', "%$keyword%");
            })
            ->with('leaderDetails.getLeaderCoreParty')
            ->orderBy('order_column')
            ->limit(10)
            ->get();
        
          
            $modifiedResults = $userResults->map(function ($user) {
                $displayName = $user->firstName.' '. $user->lastName;
                $partyId = ($user->leaderDetails && $user->leaderDetails->getLeaderCoreParty) ? $user->leaderDetails->getLeaderCoreParty->corePartyId : null;
                if($partyId!='')
                {
                    $party = Party::find($partyId)->nameAbbrevation;
                    $displayName = $displayName. '-' . $party;
                }
                else
                {
                    $party = null;  
                    $displayName = $displayName;

                }
                return [
                    'name' => $user->userName,
                    'displayName' =>  $displayName,
                    'partyName' =>  $party
                ];
            });
            
            $results = $partyResults->concat($assemblyResults)->concat($lokSabhaResults)->concat($modifiedResults);
            // $results = $partyResults;
            $results = $results->sortBy(function ($item) use ($keyword) {
                // Sort by whether the name starts with the keyword
                return strpos(strtolower($item['displayName']), strtolower($keyword)) === 0 ? 0 : 1;
            });
            // return response()->json(['status' => 'success', 'message' => "Result for mention", "result" => $results], 200);
            return response()->json(['status' => 'success', 'message' => "Result for mention", "result" => $results->values()], 200);
        } catch (\Exception $e) {
            return $e->getMessage();
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