<?php

namespace App\Http\Controllers\MinistryController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\Ministry;
use Illuminate\Http\Request;

class MasterMinsitryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
       /**
     * @OA\Get(
     *     path="/api/ministry",
     *     summary="Fetch all Ministry",
     *     tags={"Master - Ministery"},
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
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $ministries = Ministry::select('ministryName', 'id') 
            ->paginate($perPage);
                    return response()->json(['status' => 'success', 'message' => 'List of All Ministry', 'result' => $ministries], 200);
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
    public function destroy(string $id)
    {
        //
    }
}