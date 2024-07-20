<?php

namespace App\Http\Controllers\DepartmentController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\centralMinistryDepartment;
use App\Models\StateMinistryDepartment;
use Illuminate\Http\Request;

class MasterDepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     /**
     * @OA\Get(
     *     path="/api/department",
     *     summary="Get all department",
     *     tags={"Master - Department"},
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
            $stateDepartment = StateMinistryDepartment::limit(10)->select('departmentName')->get();
            $centralDepartment = centralMinistryDepartment::limit(10)->select('departmentName')->get();
            $results = $stateDepartment->concat($centralDepartment);
            return response()->json(['status' => 'success', 'message' => "Result for departmentList", "result" => $results], 200);
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