<?php

namespace App\Http\Controllers;

use App\Models\LokSabhaConsituency;
use App\Models\StateLokSabha;
use Illuminate\Http\Request;

class LoksabhaController extends Controller
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
     *     path="/api/loksabha/{id}",
     *     summary="Fetch all state assembly",
     *     tags={"Master - Loksabha"},
     *  @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the state to get loksabha list",
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
    public function show(string $id)
    {
        $loksabhaParties = StateLokSabha::where('stateId', $id)->get();

        if ($loksabhaParties->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No Loksabha  records found for the given state'], 404);
        }

        $loksabhaNames = [];

        foreach ($loksabhaParties as $loksabhaParty) {
            $loksabhaId = $loksabhaParty->loksabhaId;
            $loksabha = LokSabhaConsituency::find($loksabhaId);

            if ($loksabha) {
                $loksabhaNames[] = $loksabha;
            }
        }

        return response()->json(['status' => 'success', 'message' => 'All Loksabha Seat List', 'result' => $loksabhaNames], 200);

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
