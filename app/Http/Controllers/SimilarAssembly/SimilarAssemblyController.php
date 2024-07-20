<?php

namespace App\Http\Controllers\SimilarAssembly;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LogActivity;
use App\Models\MasterHashTag;
use App\Helpers\HttpHelper;
use App\Models\AssemblyConsituency;
use App\Models\Party;
use App\Models\User;
use App\Models\UserDetails;
use Auth;

class SimilarAssemblyController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/similarassembly",
     *     summary="Fetch all Similar Constituency",
     *     tags={"FetchPost"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found"
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function index(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $page = $request->input('page', 1); // Default to 1 if not specified
            $fetchAssembly = Self::similarassembliesByUser( $userId,$page);
            return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $fetchAssembly], 200);
        }catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    public static function similarassembliesByUser($userId,$page)
    {
        $assemblyDet = UserDetails::where('userId',$userId)->first();
        $assemblyid = $assemblyDet->assemblyId;
        if(!$assemblyid) {
            return [];
        }
        $similarAssembly = HttpHelper::similarAssembly($assemblyid);
        $assemblyIds = array_column($similarAssembly, 'id');
        $perPage = 10; // Default to 10 if not specified
        $fetchAssembly = AssemblyConsituency::whereIn('id', $assemblyIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchAssembly->getCollection()->transform(function ($item) use ($fetchAssembly) {
            $item->currentPage = $fetchAssembly->currentPage();
            return $item;
        });
        return $fetchAssembly;
    }

    public static function similarassembliesByUserTest($userId,$page)
    {
        $assemblyDet = UserDetails::where('userId',$userId)->first();
        $assemblyid = $assemblyDet->assemblyId;
        if(!$assemblyid) {
            return [];
        }
        $similarAssembly = HttpHelper::similarAssembly($assemblyid);
        $assemblyIds = array_column($similarAssembly, 'id');
        $perPage = 10; // Default to 10 if not specified
        $fetchAssembly = AssemblyConsituency::whereIn('id', $assemblyIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchAssembly->getCollection()->transform(function ($item) use ($fetchAssembly) {
            $item->currentPage = $fetchAssembly->currentPage();
            return $item;
        });
        return $fetchAssembly;
    }

    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/similarparties",
     *     summary="Fetch all Similar Parties",
     *     tags={"FetchPost"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found"
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    
     public function similarparties(Request $request)
    {
        try {
            $page = $request->input('page', 1); 
            $fetchParty = Self::similarpartiesByUser( $page);
            return response()->json(['status' => 'success', 'message' => "All Post Result", "result" => $fetchParty], 200);
        }catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    public static function similarpartiesByUser($page)
    {
        $similarParties = HttpHelper::similarParties();
        $partyIds = array_column($similarParties, 'id');
        $perPage = 10; 
        $fetchParty = Party::whereIn('id', $partyIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchParty->getCollection()->transform(function ($item) use ($fetchParty) {
            $item->currentPage = $fetchParty->currentPage();
            return $item;
        });
        return $fetchParty;
    }
    public static function similarpartiesByUserTest($page)
    {
        $similarParties = HttpHelper::similarParties();
        $partyIds = array_column($similarParties, 'id');
        $perPage = 10; 
        $fetchParty = Party::whereIn('id', $partyIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchParty->getCollection()->transform(function ($item) use ($fetchParty) {
            $item->currentPage = $fetchParty->currentPage();
            return $item;
        });
        return $fetchParty;
    }

    /**
     * @OA\Get(
     *     path="/api/similarleaders",
     *     summary="Fetch all Similar Leaders",
     *     tags={"FetchPost"},
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found"
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function similarleaders(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $page = $request->input('page', 1);
            $fetchLeader = Self::similarLeadersByUser($userId, $page);
            return response()->json(['status' => 'success', 'message' => "All Leaders Result", "result" => $fetchLeader], 200);
        }catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    public static function similarLeadersByUser($userId, $page) {
        $similarLeaders = HttpHelper::similarLeaders($userId);
        $leadersIds = array_column($similarLeaders, 'id');
        $perPage = 10; // Default to 10 if not specified

        $fetchLeader = User::whereIn('id', $leadersIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchLeader->getCollection()->transform(function ($item) use ($fetchLeader) {
            $item->currentPage = $fetchLeader->currentPage();
            return $item;
        });

        return $fetchLeader;
    }

    public static function similarLeadersByUserTest($userId, $page) {
        $similarLeaders = HttpHelper::similarLeaders($userId);
        $leadersIds = array_column($similarLeaders, 'id');
        $perPage = 10; // Default to 10 if not specified

        $fetchLeader = User::whereIn('id', $leadersIds)->paginate($perPage, ['*'], 'page', $page);
        $fetchLeader->getCollection()->transform(function ($item) use ($fetchLeader) {
            $item->currentPage = $fetchLeader->currentPage();
            return $item;
        });

        return $fetchLeader;
    }
}
