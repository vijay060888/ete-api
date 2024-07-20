<?php

namespace App\Http\Controllers\PartyController;

use App\Helpers\PartyProfileDetails;
use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use Illuminate\Http\Request;

class PartyManagementController extends Controller
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
        //
    }

        /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/api/viewPartyPage/{id}",
     *     summary="Get Party Profile Details",
     *     tags={"Party Management"},
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
        try {
            $partyId = $id;
            $canEdit="False";
            $currentPage = request('page', 1);
            if ($partyId == '') {
                return response()->json(['status' => 'error', 'message' => "Missing PartyId"], 404);
            }
            $partyViewId = request('partyId');
            $party = PartyProfileDetails::getPartyDetails($currentPage,$partyId,$canEdit,$partyViewId);
            return response()->json(['status' => 'success', 'message' => 'Party Profile details', 'result' => $party], 200);
        } catch (\Exception $e) {
            \App\Helpers\LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
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
