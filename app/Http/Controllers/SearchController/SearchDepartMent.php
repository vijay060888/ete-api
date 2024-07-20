<?php

namespace App\Http\Controllers\SearchController;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\centralMinistryDepartment;
use App\Models\StateMinistryDepartment;
use Illuminate\Http\Request;

class SearchDepartMent extends Controller
{
    /**
     * Display a listing of the resource.
     */
         /**
     * @OA\Post(
     *     path="/api/searchDepartment",
     *     summary="Search Department",
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
    public function searchDepartment(Request $request)
    {
        try{
      $keyword=$request->keyword;
      $StateMinistryDepartment = StateMinistryDepartment::where('departmentName', 'like', "%$keyword%")->select('departmentName')->limit(10)->get();
      $centralMinistryDepartment = centralMinistryDepartment::where('departmentName', 'like', "%$keyword%")->select('departmentName')->limit(10)->get();
      $results = $StateMinistryDepartment->concat($centralMinistryDepartment);
      return response()->json(['status' => 'success', 'message' =>"Result for departmentList", "result" => $results], 200);
    } catch (\Exception $e) {
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
