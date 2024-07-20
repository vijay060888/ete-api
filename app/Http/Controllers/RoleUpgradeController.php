<?php

namespace App\Http\Controllers;

use App\Helpers\Action;
use App\Helpers\LogActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\RoleUpgrade;
use Illuminate\Http\Request;
use Auth;

class RoleUpgradeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

 
    public function index()
    {
        try {
            $user = auth()->user();
            $checkUpgrade = RoleUpgrade::where('requestedBy', $user->id)->first();

            if ($checkUpgrade != '') {
                return response()->json(['status' => 'success', 'message' => 'Request details', 'result' =>['status'=> $checkUpgrade->requestStatus,'validTill' =>$checkUpgrade->validTill] ], 200);
            } else {
                return response()->json(['status' => 'server error', 'message' => 'You have not requested for any upgration'], 400);

            }
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
     *     path="/api/upgrade",
     *     summary="Send Upgrade Request",
     *     tags={"Profile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"upgradeTo"},
     *         @OA\Property(property="upgradeTo", type="string", example="Leader/Party"),
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
            $user = auth()->user();
            $existingRole = $user->getRoleNames();
            $request->validate([
                'upgradeTo' => 'required',
            ]);
            
            $role = Role::where('name', $request->upgradeTo)->first();
            if ($request->upgradeTo == $existingRole[0]) {
                return response()->json(['status' => 'server error', 'message' => "You are already a " . $existingRole[0]], 400);
            }
            if ($role != '') {
                $existingRequest = RoleUpgrade::where('requestedBy', $user->id)
                    ->where('requestStatus', 'Pending')
                    ->first();
                    if ($existingRequest != '') {
                    return response()->json(['status' => 'server error', 'message' => 'Cannot create duplicate  upgrade request.'], 400);
                }

                RoleUpgrade::create([
                    'requestedBy' => $user->id,
                    'requestFor' => $role->id,
                    'requestStatus' => 'Pending',
                    'createdBy' => $user->id,
                    'updatedBy' => $user->id,
                ]);

            /* ============================= sending email ============================== */
            $template = Action::getTemplate('role upgrade');
            $adminUser = User::role('Super Admin')->first();
            $fullName = $adminUser->getFullName();
            $url = config('app.url');
            if($template['type']=="template"){
                foreach($template['data'] as $d){
                    $content = str_replace(["{AdminName}", "{citizenName}", "{role}"], [$fullName, $user->getFullName(), $request->upgradeTo], $d->content);
                    $mail['content']=$content;
                    $mail['email']=$adminUser->email;
                    $mail['subject']=$d->subject;
                    $mail['fileName']="template";
                    $mail['cc']='';
                    if($d->cc!=null){
                        $mail['cc']=$d->cc;
                    }
                  
                }
                Action::sendEmail($mail);
            }
            /* ============================= sending email ============================== */



            /* ============================= sending push notification ============================== */
                $userId = $adminUser->userDetails->userId;
                $replaceArray = ['{name}' => Auth::user()->name];
                $notificationType = 'role_upgrade';
                $UserType = 'Super Admin';
                $getNotification = Action::getNotification('superAdmin', $notificationType);
                Action::createNotification($userId, $UserType, $adminUser->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */




            } else {
                return response()->json(['status' => 'server error', 'message' => 'No such upgrade option available.'], 400);

            }
            return response()->json(['status' => 'success', 'message' => 'Your upgrade request is created successfully.'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
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