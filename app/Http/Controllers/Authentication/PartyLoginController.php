<?php

namespace App\Http\Controllers\Authentication;

use App\Http\Controllers\Controller;
use App\Models\PartyLogin;
use App\Models\User;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Auth;
use App\Models\UserToken;

class PartyLoginController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Post(
     * path="/api/partyLogin",
     * operationId="partyLogin",
     * tags={"Authentication"},
     * summary="Party Login",
     * description="Login Party Here",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={"userName", "password"},
     *               @OA\Property(property="userName", type="string", example=""),
     *               @OA\Property(property="password", type="string", format="password", example="")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=201,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=200,
     *          description="Login Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * )
     */
    public function login(Request $request)
    {
        $user = User::where('userName', $request->input('userName'))->first();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        if (!$user->hasRole('Leader')) {
            return response()->json(['status' => 'error', 'message' => 'You are not authorized to access this resource.'], 404);
        }

        $credentials = request(['userName', 'password']);
        Auth::shouldUse('party-api');
        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['status' => 'error', 'message' => "Wrong Username or Password"], 404);
        }

        $user = auth()->user();
        $user->loginCount++;
        $user->save();

        return $this->respondWithToken($token);
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
     * @OA\Post(
     *     path="/api/partyLogout/",
     *     summary="Party Logout",
     *     tags={"Authentication"},
     *     operationId="partyLogout",
     *     @OA\Response(
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
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
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
    /**
     * @OA\Post(
     *     path="/api/saveTokenForParty",
     *     summary="Save Token for Party",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"fcmToken"},
     *         @OA\Property(property="fcmToken", type="string", example="fcmToken"),
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
    public function saveTokenForParty(Request $request)
    {
        $userId = Auth::user()->id;
        $deviceKey = $request->fcmToken;
        if ($deviceKey != null || $deviceKey != '') {
            $partyLogin = PartyLogin::where('userId', $userId)->exists();
            if ($partyLogin) {
                PartyLogin::where('userId', $userId)->update(['deviceKey' => $deviceKey]);
            }
        }
        return response()->json(['status' => 'success', 'message' => 'Party Key Saved'], 200);

    }
}
