<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Http\Request;
use JWTAuth;

class UserTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     /**
     * @OA\Get(
     *     path="/api/checkuserType",
     *     summary="Fetch all checkuserType",
     *     tags={"Authentication"},
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
    public function index(Request $request)
    {
        try {
            $a=JWTAuth::getToken();
            $token= JWTAuth::getPayload($a)->toArray();
            $jwt = JWTAuth::parseToken()->authenticate();
            return response()->json(['status' => 'success', 'message' => 'UserType Details', 'result' =>['userType' => $token['userType']] ], 200);

        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $e) {
            $jwt = false;
            return response()->json(['status' => 'error','message'=>'Unauthorized'],401);
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
