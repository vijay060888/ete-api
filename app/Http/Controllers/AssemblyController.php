<?php

namespace App\Http\Controllers;

use App\Models\AssemblyConsituency;
use App\Models\AssemblyParty;
use App\Models\StateAssembly;
use Illuminate\Http\Request;

class AssemblyController extends Controller
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
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/assembly/{id}",
     *     summary="Fetch all state assembly",
     *     tags={"Master - Assembly"},
     *  @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the state to get assembly list",
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
        $assemblyParties = StateAssembly::where('stateId', $id)->get();

        if ($assemblyParties->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No AssemblyParty records found for the given stateId'], 404);
        }

        $assemblyNames = [];

        foreach ($assemblyParties as $assemblyParty) {
            $assemblyId = $assemblyParty->assemblyId;
            $assembly = AssemblyConsituency::find($assemblyId);

            if ($assembly) {
                $assemblyNames[] = $assembly;
            }
        }

        return response()->json(['status' => 'success', 'message' => 'All Assemblies List', 'result' => $assemblyNames], 200);

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