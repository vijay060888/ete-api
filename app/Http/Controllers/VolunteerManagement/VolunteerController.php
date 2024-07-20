<?php

namespace App\Http\Controllers\VolunteerManagement;

use Auth;
use App\Models\User;
use App\Models\Party;
use App\Models\Leader;
use App\Models\Volunteer;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Helpers\EncryptionHelper;
use App\Models\VolunteerDepartMent;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class VolunteerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/volunteer",
     *     summary="Fetch all Volunteers",
     *     tags={"Volunteer"},
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
            $currentPage = request('page', 1);
            $keyword = request('keyword');
            $perPage = env('PAGINATION_PER_PAGE', 10);
            $partyId = request('partyId');
            $volunteersCreatedTypeId = $partyId ? $partyId : Auth::user()->id;
            $volunteer = Volunteer::where('volunteersCreatedTypeId', $volunteersCreatedTypeId)
                ->whereHas('user', function ($query) use ($keyword) {
                    $query->where('firstName', 'like', "%$keyword%");
                })
                ->with('user')
                ->get();
            $volunteerArray = $volunteer->map(function ($volunteers) use ($volunteersCreatedTypeId) {
                $name = $volunteers->user->firstName . " " . $volunteers->user->lastName;
                if ($volunteers->volunteersCreatedType == "Party") {
                    $party = Party::find($volunteersCreatedTypeId);
                    $volunterByName = $party->name;
                } else {
                    $user = User::find($volunteersCreatedTypeId);
                    $volunterByName = $user->firstName . " " . $user->lastName;

                }
                $formattedDate = \Carbon\Carbon::parse($volunteers->createdAt)->format('d/m/Y');
                return [
                    'id' => $volunteers->id,
                    'volunteercode' => $volunteers->volunteercode,
                    'volunterToName' => $name,
                    'volunterByName' => $volunterByName,
                    'volunteerPhone' => $volunteers->user->phoneNumber,
                    'status' => $volunteers->status,
                    'createdAt' => $formattedDate,
                ];
            });

            $desiredTotal = $volunteerArray->count();
            $pagedPosts = $volunteerArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return response()->json(['status' => 'success', 'message' => 'List of Volunteers', 'result' => $list], 200);
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
    /**
     * @OA\Post(
     *     path="/api/volunteer",
     *     summary="Add Volunteer",
     *     tags={"Volunteer"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Volunteer",
     *     @OA\JsonContent(
     *         required={"partyId","volunteersTo","volunterDepartmentType","dateOfJoining","comments","status" ,"maxEducation","reportingManager"},
     *         @OA\Property(property="partyId", type="string", example="partId"),
     *        @OA\Property(property="volunterDepartmentType", type="string", example="volunterDepartmentType"),
     *        @OA\Property(property="volunteersTo", type="string", example="userId"),
     *       @OA\Property(property="maxEducation", type="string", example="maxEducation"),
     *        @OA\Property(property="dateOfJoining", type="string", example="eretf232"),
     *        @OA\Property(property="reportingManager", type="string", example="reportingManager"),
     *        @OA\Property(property="professionalExperince", type="string", example="professionalExperince"),
     *        @OA\Property(property="comments", type="string", example="eretf232"),
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
    public function store(Request $request)
    {
        try {
        $partyId = $request->partyId;
        $roleName = $partyId ? "Party" : Auth::user()->getRoleNames()[0];
        $userId = $partyId ? $partyId : Auth::user()->id;
        $maxEducation = $request->input('maxEducation');
        Volunteer::create([
            'id' => \DB::raw('gen_random_uuid()'),
            'volunteersTo' => $request->input('volunteersTo'),
            'volunteersCreatedType' => $roleName,
            'volunteersCreatedTypeId' => $userId,
            'volunterDepartmentType' => $request->input('volunterDepartmentType'),
            'dateOfJoining' => $request->input('dateOfJoining'),
            'comments' => $request->input('comments'),
            'status' => 'Active',
            'userId' => $userId,
            'profesionalExperience' => $request->input('profesionalExperience'),
            'reportingManager' => $request->input('reportingManager'),
            'maxEducation' => $maxEducation,
            'createdBy' => Auth::user()->id,
            'updatedBy' => Auth::user()->id,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
        if ($roleName == "Party") {
            $party = Party::find($userId);
            $party->voluntercount++;
            $party->save();
        } else {
            $leader = Leader::where('leadersId', $userId)->first();
            $leader->voluntercount++;
            $leader->save();
        }
        return response()->json(['status' => 'success', 'message' => 'Volunteer Saved Succesfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

      /**
     * @OA\Get(
     *     path="/api/volunteer/{id}",
     *     summary="Fetch Volunteer by id",
     *     tags={"Volunteer"},
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
    public function show(string $id)
    {
        $volunteer = Volunteer::where('id', $id)
            ->whereHas('user', function ($query) {
            })
            ->with('user')
            ->get();
        $volunteerArray = $volunteer->map(function ($volunteers) {
            $name = $volunteers->user->firstName . " " . $volunteers->user->lastName;
            $formattedDate = \Carbon\Carbon::parse($volunteers->createdAt)->format('d/m/Y');
            $name = $volunteers->user->firstName . " " . $volunteers->user->lastName;
            $volunterDepartmentTypeName = VolunteerDepartMent::find($volunteers->volunterDepartmentType);
            return [
                'id' => $volunteers->id,
                'volunteersTo' => $volunteers->volunteersTo,
                'volunteersToName' => $name,
                'volunterDepartmentType' => $volunteers->volunterDepartmentType,
                'volunterDepartmentTypeName'  =>$volunterDepartmentTypeName->departmentName,
                'dateOfJoining' => $volunteers->dateOfJoining,
                'comments' => $volunteers->comments,
                'status' => 'InActive',
                'profesionalExperience' => $volunteers->profesionalExperience,
                'reportingManager' => $volunteers->reportingManager,
                'maxEducation' => $volunteers->maxEducation,
            ];
        });
        return response()->json(['status' => 'success', 'message' => 'Volunteer Saved Succesfully' ,'result'=>$volunteerArray], 200);

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

    /**
     *   @OA\Put(
     *   path="/api/volunteer/{id}",
     *     summary="Update volunteer by id",
     *     tags={"Volunteer"},
     * 
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId","volunteersTo","volunterDepartmentType","dateOfJoining","comments","status" ,"maxEducation","reportingManager"},
     *         @OA\Property(property="partyId", type="string", example="partId"),
     *        @OA\Property(property="volunterDepartmentType", type="string", example="volunterDepartmentType"),
     *        @OA\Property(property="volunteersTo", type="string", example="userId"),
     *       @OA\Property(property="maxEducation", type="string", example="maxEducation"),
     *        @OA\Property(property="dateOfJoining", type="string", example="eretf232"),
     *        @OA\Property(property="reportingManager", type="string", example="reportingManager"),
     *        @OA\Property(property="professionalExperince", type="string", example="professionalExperince"),
     *        @OA\Property(property="comments", type="string", example="eretf232"),
     *        @OA\Property(property="status", type="string", example="InActive"),
     *      ),
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Error while updating "
     *   ),
     *    security={{ "apiAuth": {} }}
     *)
     **/
    public function update(Request $request, string $id)
    {
        try {
            $volunteer = Volunteer::find($id);
            $status = $request->input('status') ?: $volunteer->status;
            $volunteersTo = $request->input('volunteersTo') ?: $volunteer->volunteersTo;
            $volunterDepartmentType = $request->input('volunterDepartmentType') ?: $volunteer->volunterDepartmentType;
            $dateOfJoining = $request->input('dateOfJoining') ?: $volunteer->dateOfJoining;
            $comments = $request->input('comments') ?: $volunteer->comments;
            $reportingManager = $request->input('reportingManager') ?: $volunteer->reportingManager;
            $maxEducation = $request->input('maxEducation') ?: $volunteer->maxEducation;
            Volunteer::where('id', $id)->update([
                'status' => $status,
                'volunteersTo' => $volunteersTo,
                'volunterDepartmentType' => $volunterDepartmentType,
                'dateOfJoining' => $dateOfJoining,
                'profesionalExperience' => $request->professionalExperince,
                'comments' => $comments,
                'reportingManager' => $reportingManager,
                'maxEducation' => $maxEducation,
            ]);


            return response()->json(['status' => 'success', 'message' => 'Volunteer Updated Successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Server Error'], 404);
        }
    }


    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/volunteer/{id}",
     *     summary="Delete Volunteer  id",
     *     tags={"Volunteer"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
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
    public function destroy(string $id)
    {
        try {
            $volunteer = Volunteer::find($id);
            $volunteer->delete();
            return response()->json(['status' => 'success', 'message' => 'Volunteer deleted successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Server Error'], 404);
        }
    }
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/volunteerList",
     *     summary="Fetch all volunteerList",
     *     tags={"Volunteer"},
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
    public function volunteerList()
    {
        try {
            $userId = Auth::user()->id;
            $data = User::role('Citizen')
                ->whereNotIn('id', function ($query) use ($userId) {
                    $query->select('volunteersTo')
                        ->from('volunteers')
                        ->where('volunteersTo', $userId);
                })
                ->get();
            return response()->json(['status' => 'success', 'message' => 'Volunteer Lists', 'result' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/getVolunteerdepartMentList",
     *     summary="Fetch all getdepartMentList",
     *     tags={"Volunteer"},
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
    public function getVolunteerdepartMentList()
    {
        try {
            $data = VolunteerDepartMent::select('id', 'departmentName')->get();
            return response()->json(['status' => 'success', 'message' => 'Department Lists', 'result' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Post(
     *     path="/api/searchVolunterWithAadhar",
     *     summary="Fetch all search Volunter With Aadhar",
     *     tags={"Volunteer"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Search keyword for volunteer",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="keyword",
     *                     type="string",
     *                     example="John",
     *                     description="The search keyword for volunteer"
     *                 ),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         )
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


     public function searchVolunterWithAadhar(Request $request)
     {
          try {
         $keyword = $request->keyword;
         $userId = Auth::user()->id;
         if ($keyword != '') {
             $aadhar = EncryptionHelper::encryptString($keyword);
 
             $user = User::role('Citizen')
                 ->where('aadharNumber', $aadhar)->first();
 
             if (!$user) {
                 return response()->json(['status' => 'error', 'message' => 'No registered Citizens found with the given Aadhar number '], 404);
             }
             if (Volunteer::where('volunteersTo', $user->id)->exists()) {
                 return response()->json(['status' => 'error', 'message' => 'Already volunter by some other leader or party'], 404);
             }
             $fullName = $user->firstName . ' ' . $user->lastName;
             $userId = $user->id;
 
             $userDetails = [
                 'userId' => $userId,
                 'name' => $fullName,
             ];
 
             return response()->json(['status' => 'success', 'message' => 'Citizen Details', 'result' => $userDetails], 200);
         }
          } catch (\Exception $e) {
              LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
              return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
          }
     }


      /**
     * @OA\Get(
     *     path="/api/VolunterreportingManager",
     *     summary="Fetch all Reporting Manager",
     *     tags={"Volunteer"},
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
     public function VolunterreportingManager()
     {
         try {
             $partyId = request('partyId');
             $leaderorPartyId = $partyId ? $partyId : Auth::user()->id;
            
             if ($partyId != '') {
                $party = Party::find($leaderorPartyId);
                $userorPartyId = $party->id;
                $name = $party->name;
            } else {
                $user = User::find($leaderorPartyId);
              $userorPartyId = $user->id;
                $name = $user->firstName . " " . $user->lastName;
            }
        
            $ownUser = [
                "userId" => $userorPartyId,
                "name" => $name,
            ];
            $volunteer = Volunteer::where('volunteersCreatedTypeId', $leaderorPartyId)->get();
             $processedData =  $volunteer ->map(function ( $volunteer ) {
                 $userId =  $volunteer->volunteersTo;
                 $users = User::find($userId);
                 $firstName = $users->firstName;
                 $lastName = $users->lastName;
                 return [
                     'userId' => $users->id,
                     'name' => $firstName . ' ' . $lastName
                 ];
             });
             $mergedData =  array_merge([$ownUser], $processedData->toArray());
             return response()->json(['status' => 'success', 'message' => 'Reporting Manager Details', 'result' =>$mergedData], 200);
 
         } catch (\Exception $e) {
             LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
             return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
         }
     }
}
