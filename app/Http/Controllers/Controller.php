<?php

namespace App\Http\Controllers;

use App\Models\Party;
use App\Models\PartyLogin;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Auth;
/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Next Govt API Documentation",
 *      description="List of All API's",
 *      @OA\Contact(
 *          email=""
 *      )
 * )
 *
 * @OA\SecurityScheme(
 *     type="http",
 *     description="Login with email and password to get the authentication token",
 *     name="Token Based",
 *     in="header",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     securityScheme="apiAuth",
 * )
 * @OA\Tag(
 *     name="Authentication",
 *     description="API for authentication"
 * )
 * @OA\Tag(
 *     name="Registration",
 *     description="API for registration"
 * )
 * @OA\Tag(
 *     name="Forget Password",
 *     description="API for password reset (Forget Password)"
 * )
 * @OA\Tag(
 *     name="Master - Roles",
 *     description="Manage Role (Role Management)"
 * )
 * @OA\Tag(
 *     name="Master - State",
 *     description="Manage State (State Management)"
 * )
 * @OA\Tag(
 *     name="Master - Party",
 *     description="Manage Party (Master Party Page)"
 * )
 * @OA\Tag(
 *     name="Master - Assembly",
 *     description="Manage Assembly (Assembly Management)"
 * )
 * @OA\Tag(
 *     name="Master - Loksabha",
 *     description="Manage Loksabha (Assembly Management)"
 * )
 * @OA\Tag(
 *     name="Profile",
 *     description="API for User Profile (Profile)"
 * )
 * @OA\Tag(
 *     name="LeaderProfile",
 *     description="API for Leader Profile (Profile)"
 * )
 * @OA\Tag(
 *     name="PartyProfile",
 *     description="API for Party Profile (Profile)"
 * )
 *    @OA\Tag(
 *     name="Party Management",
 *     description="API for All Party Related"
 * )
 *     @OA\Tag(
 *     name="Leader Management",
 *     description="API for All Leader Related"
 * )
 *     @OA\Tag(
 *     name="Follow Management",
 *     description="API Follow and unfollow management"
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function respondWithToken($token){
        $currentGuard = Auth::getDefaultDriver();
        $party=PartyLogin::where('userId',Auth::user()->id)->get();
         $partyName=[];
           foreach($party as $parties){
             $partyname=Party::where('id',$parties->partyId)->select('name','id as partyId','logo')->get();
             $partyName=$partyname;
           }
        
        $response = [
            'token' => $token,
            'token_type' => 'bearer',
            'loginCount' => Auth::user()->loginCount,
            'isAadharPresent' => (auth()->user()->aadharNumber) ? true : false, 
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
        
        if ($currentGuard !== 'party-api') {
            $response['userType'] = Auth::user()->getRoleNames()[0];
        }else{
            $response['userType'] = 'Leader Party';
            $response['partyAcces'] = $partyName;

        }
        return response()->json($response, 200);

    }
}
