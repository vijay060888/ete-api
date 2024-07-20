<?php

namespace App\Http\Controllers;

use App\Models\SMSTemplate;
use Illuminate\Http\Request;

class SMSTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/broadcastingsms",
     *     summary="Fetch all BroadCasting",
     *     tags={"Master - SMS"},
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
        $sms = SMSTemplate::where('isbroadcastingtemplate', true)
            ->select('id', 'SMSType')
            ->get();

        $sms = $sms->map(function ($item) {
            $item->SMSType = ucfirst(strtolower($item->SMSType));
            return $item;
        });
        return response()->json(['status' => 'success', 'result' => $sms], 200);
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
