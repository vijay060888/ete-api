<?php

namespace App\Http\Controllers\PartyController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\PartyLevelRole;
use Illuminate\Http\Request;

class PartyLevelRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  
    public function index(Request $request)
    {
      
    }
    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    
/**
 * @OA\Get(
 *     path="/api/getPartyLevelRole/{id}",
 *     summary="Fetch all party role assembly",
 *     tags={"Party Management"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="The ID of the state to get party level roles",
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
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Error / Data not found"
 *     ),
 *     security={{ "apiAuth": {} }}
 * )
 */

    public function show(string $id)
    {
        try{
            $partyId=$id;
            if($partyId==''){
                return response()->json(['status' => 'error', 'message' => 'Party is required', ], 400);
            }
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $partyRole = PartyLevelRole::select('roleName', 'id') 
            ->where('partyId', $partyId)
            ->paginate($perPage);
            return response()->json(['status' => 'success', 'message' => 'List of All Ministry', 'result' => $partyRole], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
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
