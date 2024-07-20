<?php

namespace App\Http\Controllers\HelpManagement;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\Help;
use Illuminate\Http\Request;

class HelpManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
      /**
     * @OA\Get(
     *     path="/api/help",
     *     summary="Fetch all help",
     *     tags={"Master - Help"},
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
         try{
            $keyword = request('keyword');
            $payment_related_queries=Help::where('queryType','payment_related_queries')->where('question','like', "%$keyword%")->get();
            $user_related_queries=Help::where('queryType','user_related_queries')->where('question','like', "%$keyword%")->get();
            return response()->json(['status' => 'success','message' => 'List of Help','paymentRelatedQueries'=>$payment_related_queries, 'userRelatedQueries'=>$user_related_queries],200);
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);
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
    /**
     * @OA\Get(
     *     path="/api/help/{id}",
     *     summary="Fetch help by id",
     *     tags={"Master - Help"},
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
    public function show($id)
    {
        try{
            $data=Help::find($id);
            return response()->json(['status' => 'success','message' => 'Help Questions','result'=>$data->answers],200);                       
        }catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(),$e->getTraceAsString());
            return response()->json(['status' => 'error','message'=>"Server Error"],404);
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
