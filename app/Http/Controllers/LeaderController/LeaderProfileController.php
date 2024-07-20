<?php

namespace App\Http\Controllers\LeaderController;

use App\Helpers\Action;
use App\Helpers\customPagination;
use App\Helpers\customPaginationPageRequest;
use App\Helpers\FollowerHelper;
use App\Helpers\LeaderProfileDetails;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\AssemblyConsituency;
use App\Models\AssignPartyToLeaders;
use App\Models\CorePartyChangeRequest;
use App\Models\Leader;
use App\Models\LeaderCoreParty;
use App\Models\LeaderElectionHistory;
use App\Models\LeaderMinistry;
use App\Models\LokSabhaConsituency;
use App\Models\Ministry;
use App\Models\PageRequest;
use App\Models\Party;
use App\Models\PartyLogin;
use App\Models\State;
use App\Models\UserDetails;
use App\Models\User;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;

class LeaderProfileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/leaderProfile",
     *     summary="Leader Profile Details",
     *     tags={"LeaderProfile"},
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
    public function index()
    {
        try {
            $userId = Auth::user()->id;
            $canEdit = "True";
            $currentPage = request('page', 1);
            $showStream = false;
            $leaderDetails = LeaderProfileDetails::getLeaderProfileDetails($userId, $canEdit, $currentPage,null, $showStream);
            return response()->json(['status' => 'success', 'message' => 'Leader Profile details', 'result' => $leaderDetails], 200);
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
     *
     * @OA\Put(
     *     path="/api/leaderProfile/{id}",
     *     summary="Update LeaderProfile details",
     *     tags={"LeaderProfile"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the LeaderProfile to update",
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"biography", "mission", "vision", "officeAddress", "secondPhoneNumber", "social" ,"email","phoneNumber","Profile","ministryType"},
     *             @OA\Property(property="biography", type="string", example="biography data", description="Biography"),
     *             @OA\Property(property="mission", type="string", example="Mission data", description="Mission"),
     *             @OA\Property(property="vision", type="string", example="Vision data", description="Vision"),
     *             @OA\Property(property="officeAddress", type="string", example="Office Address data", description="Office Address"),
     *             @OA\Property(property="secondPhoneNumber", type="string", example="1234567890", description="Second Phone Number"),
     *            @OA\Property(property="email", type="string", example="email@emailcom", description="Email Address"),
     *            @OA\Property(property="phoneNumber", type="string", example="1234567890", description="Phone Number"),
     *          @OA\Property(property="Profile", type="string", example="imageurl", description="Image URL"),
     *          @OA\Property(property="timelineYear", type="string", example="2022", description="TimeLineYear"),
     *          @OA\Property(property="timelineHeading", type="string", example="heading", description="timelineHeading"),
     *         @OA\Property(property="timelineDescriptions", type="string", example="heading", description="timelineDescriptions"),
     *        @OA\Property(property="contactPersonName", type="string", example="contactPersonName", description="contactPersonName"),
     *       @OA\Property(property="backgroundImage", type="string", example="backgroundImage", description="backgroundImage"),
     *       @OA\Property(property="ministryType", type="string", example="Ex/Current", description="Ex/Current"),
     *       @OA\Property(property="ministryName", type="string", example="ministryName", description="ministryName"),
     *       @OA\Property(property="ministryStateOrCenter", type="string", example="state/center", description="ministryStateOrCenter"),
     *     @OA\Property(property="partylevelRoleName", type="string", example="rolename", description="rolename"),
     *     @OA\Property(property="partyLevelRoleStateOrCenter", type="string", example="state/center", description="state or center"),
     *      @OA\Property(property="leaderMinistry", type="string", example="EX-MP", description="leaderMinistry"),
     *      @OA\Property(property="leaderElectionAssemblyId", type="string", example="assemblyId", description="assemnlyid"),
     *     @OA\Property(property="leaderElectionLokSabhaId", type="string", example="loksabhaID", description="loksabhaid"),
     *    @OA\Property(property="leaderElectionResult", type="string", example="win/loose", description="result"),
     * 
     *      @OA\Property(property="file", type="string", example="heading", description="fileURL"),
     *             @OA\Property(
     *                 property="social",
     *                 type="array",
     *                 @OA\Items(type="string", example="facebook"),
     *                 description="Social Handles"
     *             ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *         ),
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Error while updating",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */

    public function update(Request $request, string $id)
    {
        try {
            $leader = Leader::where('leadersId', $id)->first();
            $biography = $leader != '' ? $request->input('biography') ?? $leader->leaderBiography : null;
            $mission = $leader != '' ? $request->input('mission') ?? $leader->leaderMission : null;
            $vision = $leader != '' ? $request->input('vision') ?? $leader->leaderVision : null;
            $officeAddress = $leader != '' ? $request->input('officeAddress') ?? $leader->officeAddress : null;
            $secondPhoneNumber = $leader != '' ? $request->input('secondPhoneNumber') ?? $leader->phoneNumber2 : null;
            $social = $leader != '' ? $request->input('social') ?? $leader->social : null;
            $timelineYear = $leader != '' ? $request->input('timelineYear') ?? $leader->timelineYear : null;
            $timelineHeading = $leader != '' ? $request->input('timelineHeading') ?? $leader->timelineHeading : null;
            $timelineDescriptions = $leader != '' ? $request->input('timelineDescriptions') ?? $leader->timelineDescriptions : null;
            $file = $leader != '' ? $request->input('file') ?? $leader->file : null;
            $contactPersonName = $leader != '' ? $request->input('contactPersonName') ?? $leader->contactPersonName : null;
            $backgroundImage = $leader != '' ? $request->input('backgroundImage') ?? $leader->backgroundImage : null;
            Leader::updateOrInsert(
                ['leadersId' => $id],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'leaderBiography' => $biography,
                    'leaderMission' => $mission,
                    'leaderVision' => $vision,
                    'officeAddress' => $officeAddress,
                    'phoneNumber2' => $secondPhoneNumber,
                    'social' => $social,
                    'officialPage' => 'page1',
                    'timelineYear' => $timelineYear,
                    'timelineHeading' => $timelineHeading,
                    'timelineDescriptions' => $timelineDescriptions,
                    'file' => $file,
                    'backgroundImage' => $backgroundImage,
                    'contactPersonName' => $contactPersonName,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id,
                    'createdAt' => now(),
                    'updatedAt' => now(),
                ]
            );


            $user = User::find($id);


            $rules = [
                'email' => [

                    'email',
                    Rule::unique('users')->ignore($user->id),
                ],
                'phoneNumber' => [
                    Rule::unique('users')->ignore($user->id),
                ],
            ];

            $messages = [
                'email.unique' => 'The email address is already in use by another user.',
                'phoneNumber.unique' => 'The phone number is already in use by another user.',
            ];
            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
            }


            $email = $request->input('email') ?? $user->email;
            $phoneNumber = $request->input('phoneNumber') ?? $user->phoneNumber;
            $user->email = $email;
            $user->phoneNumber = $phoneNumber;
            $user->save();
            $userDetails = UserDetails::where('userId', $user->id)->first();
            $profileImage = $userDetails != '' ? $request->input('Profile') ?? $userDetails->profileImage : null;
            UserDetails::updateOrInsert(
                ['userId' => $user->id],
                [
                    'id' => \DB::raw('gen_random_uuid()'),
                    'profileImage' => $profileImage,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id
                ]
            );
            $ministry = $request->input('ministryName');
            $ministryType = $request->input('ministryType');
            $ministryStateOrCenter = $request->input('ministryStateOrCenter');
            if ($ministryType != '' and $ministry != '' and $ministryStateOrCenter != '') {
                $ministryId = Ministry::where('ministryName', $ministry)->first();
                LeaderMinistry::updateOrCreate(
                    ['leaderId' => Auth::user()->id],
                    ['ministryId' => $ministryId->id, 'status' => $ministryType, 'type' => $ministryType]
                );
            }
            $partylevelRole = $request->input('partylevelRoleName');
            $partyLevelRoleStateOrCenter = $request->input('partyLevelRoleStateOrCenter');
            if ($partylevelRole != '' and $partyLevelRoleStateOrCenter != '') {
                Leader::where('leadersId', $leader->leadersId)->update(['leaderPartyRole' => $partylevelRole, 'leaderPartyRoleLevel' => $partyLevelRoleStateOrCenter]);
            }
            $mainParty = LeaderCoreParty::where('leaderId', $leader->leadersId)->first();
            if ($mainParty != '') {
                $isIndependent = false;
                $mainParty = $mainParty->corePartyId;
            } else {
                $mainParty = "";
                $isIndependent = true;
            }
            $leaderMinistry = $request->input('leaderMinistry');
            $leaderElectionAssemblyId = $request->input('leaderElectionAssemblyId');
            $leaderElectionLokSabhaId = $request->input('leaderElectionLokSabhaId');
            $leaderElectionResult = $request->input('leaderElectionResult');
            $leaderElectionHistoryDetails = [
                "leaderId" => $leader->leadersId,
                'electionHistoryLeaderResult' => $leaderElectionResult,
                'isIndependent' => $isIndependent,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id
            ];
            $conditions = [
                'leaderId' => $leader->leadersId,
            ];

            if (!empty($mainParty)) {
                $conditions['partyId'] = $mainParty;
                $leaderElectionHistoryDetails['partyId'] = $mainParty;
            }
            if ($leaderElectionAssemblyId !== '') {
                $leaderElectionHistoryDetails["assemblyId"] = $leaderElectionAssemblyId;
            }
            if ($leaderElectionLokSabhaId !== '') {
                $leaderElectionHistoryDetails["loksabhaId"] = $leaderElectionLokSabhaId;
            }
            LeaderElectionHistory::updateOrCreate($conditions, $leaderElectionHistoryDetails);

            Leader::where('leadersId', $leader->leadersId)->update(['leaderMinistry' => $leaderMinistry]);
            return response()->json(['status' => 'success', 'message' => 'Leader Profile update successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */


    /**
     * @OA\Post(
     *     path="/api/createPageRequest",
     *     summary="Create Page Request",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"partyId", "pageType", "stateId", "assemblyId", "loksabhaId"},
     *             @OA\Property(property="pageType", type="string", example="State/Constituency", description="BJP-Maharashtra"),
     *             @OA\Property(property="stateId", type="string", example="1234ffd", description="stateId (if page request is state party page)"),
     *             @OA\Property(property="assemblyId", type="string", example="1234ffd", description="assemblyId (if page request is constituency party page)"),
     *             @OA\Property(property="loksabhaId", type="string", example="1234ffd", description="loksabhaId"),
     *             @OA\Property(property="partyId", type="string", example="1234ffd", description="partyId (if page request is constituency party page)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json"
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


    public function createPageRequest(Request $request)
    {
        try {
            $stateId = $request->stateId;
            $loksabhaId = $request->lokasabhaId;
            $assemblyId = $request->assemblyId;
            $pageRequest = [
                'requestedBy' => Auth::user()->id,
                'pageType' => $request->pageType,
                'stateId' => $request->stateId,
                'assemblyId' => $assemblyId,
                'lokasabhaId' => $request->lokasabId,
                'partyId' => $request->partyId,
                'requestType' => 'Create',
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
            ];
            $party = Party::where('id', $request->partyId)->first();

            if ($stateId != '' && $assemblyId == '' && $loksabhaId == '') {
                $stateName = State::where('id', $stateId)->first();
                $partyName = $party->nameAbbrevation . '-' . $stateName->name;
            }

            if ($loksabhaId != '') {
                $loksabhaName = LokSabhaConsituency::where('id', $loksabhaId)->first();
                $partyName = $party->nameAbbrevation . '-' . $loksabhaName->name;
            }
            if ($assemblyId != '') {
                $assemblyName = AssemblyConsituency::where('id', $assemblyId)->first();
                $partyName = $party->nameAbbrevation . '-' . $assemblyName->name;
            }
            if (Party::where('name', $partyName)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'This Party state or assembly page already exists'], 400);
            }
            PageRequest::create($pageRequest);


            /* ============================= sending email ============================== */
            $template = Action::getTemplate('page request create');
            $PartyLeaders = PartyLogin::where("partyId", $party->id)->get();
            $Leader = Auth::user();
            $fullName = $Leader->getFullName();

            foreach ($PartyLeaders as $PartyLeader) {
                if ($template['type'] == "template") {
                    foreach ($template['data'] as $d) {
                        $content = str_replace(["{LeaderName}", "{PartyName}", "{requestPartyname}"], [$fullName, $party->name, $partyName], $d->content);
                        $mail['content'] = $content;
                        $mail['email'] = $Leader->email;
                        $mail['subject'] = $d->subject;
                        $mail['fileName'] = "template";
                        $mail['cc'] = '';
                        if ($d->cc != null) {
                            $mail['cc'] = $d->cc;
                        }
                        Action::sendEmail($mail);
                    }
                }
            }
            /* ============================= sending email ============================== */



            /* ============================= sending push notification ============================== */
            $userId = $party->id;
            $replaceArray = ['{LeaderName}' => $fullName];
            $notificationType = 'page_create';
            $UserType = 'Party';
            $getNotification = Action::getNotification('party', $notificationType);
            Action::createNotification($userId, $UserType, $party->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */


            return response()->json(['status' => 'success', 'message' => 'Page Request Created Successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/createNewPartyRequest",
     *     summary="Create Page Request",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"partyName"},
     *             @OA\Property(property="partyName", type="string", example="BJP/Congress", description="BJP"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json"
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
    public function createNewPartyRequest(Request $request)
    {
        try {
            $pageRequest = [
                'requestedBy' => Auth::user()->id,
                'partyName' => $request->partyName,
                'requestType' => 'Create',
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
            ];
            $partyName = $request->partyName;
            if ($partyName == '') {
                return response()->json(['status' => 'error', 'message' => 'PartyName is required'], 400);
            }

            if (Party::where('name', $partyName)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'This Party  page already exists'], 400);
            }
            PageRequest::create($pageRequest);
            return response()->json(['status' => 'success', 'message' => 'Page Request Created Successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/createPageAccessRequest",
     *     summary="Create Page Request Access",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"partyId"},
     *            
     *             @OA\Property(property="partyId", type="string", example="1234ffd", description="partyId (if page request is constituency party page)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\MediaType(
     *             mediaType="application/json"
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


    public function createPageAccessRequest(Request $request)
    {
        try {
            $party = Party::where('id', $request->partyId)->get();
            if ($party == '') {
                return response()->json(['status' => 'error', 'message' => "Party you want to request for access doesn't exist"], 400);

            }
            $existingRequest = PageRequest::where('requestedBy', Auth::user()->id)
                ->where('partyId', $request->partyId)
                ->where('requestType', 'Access')
                ->exists();

            if ($existingRequest) {
                return response()->json(['status' => 'success', 'message' => 'Your previous request is still pending.'], 200);
            }

            $pageRequest = [
                'requestedBy' => Auth::user()->id,
                'partyId' => $request->partyId,
                'requestType' => 'Access',
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
            ];
            PageRequest::create($pageRequest);

            /* ============================= sending email ============================== */
            $partyDetail = Party::where('id', $request->partyId)->first();
            $template = Action::getTemplate('page access');
            $PartyLeaders = PartyLogin::where("partyId", $request->partyId)->get();
            $Leader = Auth::user();
            $fullName = $Leader->getFullName();

            foreach ($PartyLeaders as $PartyLeader) {
                if ($template['type'] == "template") {
                    foreach ($template['data'] as $d) {
                        $content = str_replace(["{LeaderName}", "{PartyName}"], [$fullName, $partyDetail->name], $d->content);
                        $mail['content'] = $content;
                        $mail['email'] = $PartyLeader->user->email;
                        $mail['subject'] = $d->subject;
                        $mail['fileName'] = "template";
                        $mail['cc'] = '';
                        if ($d->cc != null) {
                            $mail['cc'] = $d->cc;
                        }
                        Action::sendEmail($mail);
                    }
                }
            }
            /* ============================= sending email ============================== */

            /* ============================= sending push notification ============================== */
            $userId = $partyDetail->id;
            $replaceArray = ['{LeaderName}' => $fullName];
            $notificationType = 'page_access';
            $UserType = 'Party';
            $getNotification = Action::getNotification('party', $notificationType);
            Action::createNotification($userId, $UserType, $partyDetail->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */


            return response()->json(['status' => 'success', 'message' => 'Page Request Access Created Successfully'], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Get(
     *     path="/api/partyPageMangement",
     *     summary="Leader Party Page Management",
     *     tags={"LeaderProfile"},
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
    public function partyPageMangement()
    {
        try {
            $id = Auth::user()->id;
            $pageManagement = FollowerHelper::getAllPageMagenementDetailsforLeader($id);
            return response()->json(['status' => 'success', 'message' => 'Page Management Details', 'result' => $pageManagement], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }
    /**
     * @OA\Post(
     *     path="/api/changeCoreParty",
     *     summary="Change Core Party Request",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId"},
     *         @OA\Property(property="partyId", type="string", example="1212sss"),
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
    public function changeCoreParty(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $changeRequest = CorePartyChangeRequest::where('leaderId', Auth::user()->id)->first();
            if ($changeRequest != '') {
                return response()->json(['status' => 'success', 'message' => "Your previous request is still pending"], 200);
            } else {
                $createRequest = [
                    'partyId' => $partyId,
                    'leaderId' => Auth::user()->id,
                    'createdBy' => Auth::user()->id,
                    'updatedBy' => Auth::user()->id
                ];
                CorePartyChangeRequest::create($createRequest);
                return response()->json(['status' => 'success', 'message' => 'Request Created Successfully'], 200);
            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/filterAdminRoleParty",
     *     summary="filterAdminRoleParty",
     *     tags={"Filter"},
     *     @OA\Parameter(
     *         name="searchType",
     *         in="query",
     *         description="The type of search being performed (e.g., 'Loksabha').",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="Loksabha"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="searchType",
     *                 type="string",
     *                 example="LokSabha"
     *             ),
     *            
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

    public function filterAdminRoleParty(Request $request)
    {
        try {
            $searchType = $request->searchType;
            $searchType = ($searchType) && !empty($searchType) ? $searchType : '';
            $where = [];

            if ($searchType !== '') {
                array_push($where, " type = '" . $searchType . "'");
            }

            $sql = "SELECT DISTINCT id FROM parties";

            if (count($where) > 0) {
                $sql = $sql . " WHERE " . implode(" AND ", $where);
            }

            $res = DB::select($sql);
            $total = count($res);
            $per_page = env("PAGINATION", 10);
            $current_page = $request->input("page") ?? 1;
            $starting_point = ($current_page * $per_page) - $per_page;
            $sql .= " Limit $per_page OFFSET $starting_point";
            $final_result = DB::select($sql);

            $i = 0;

            foreach ($final_result as $result) {
                $party = Party::find($result->id);
                $partyLogins = PartyLogin::where('partyId', $party->id)->where('userId', Auth::user()->id)->first();
                if ($partyLogins) {
                    $final_result[$i]->name = $party->name;
                    $final_result[$i]->logo = $party->logo;
                    $final_result[$i]->type = $party->type;
                    $final_result[$i]->followercount = $party->followercount;
                    $final_result[$i]->voluntercount = $party->voluntercount;
                    $final_result[$i]->stateCode = $party->getStateCode();
                    $i++;
                } else {
                    $final_result = [];
                }

            }


            $list = new CustomPagination($final_result, $total, $per_page, $current_page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return response()->json(['status' => 'OK', 'result' => $list], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString(), $e->getCode());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }


    }


    /**
     * @OA\Get(
     *     path="/api/filterPageRequest",
     *     summary="filterAdminRoleParty",
     *     tags={"Filter"},
     *     @OA\Parameter(
     *         name="searchType",
     *         in="query",
     *         description="The type of search being performed (e.g., 'Loksabha').",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             example="Loksabha"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="searchType",
     *                 type="string",
     *                 example="LokSabha"
     *             ),
     *            
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
    public function filterPageRequest(Request $request)
    {
        try {
            $userId = Auth::user()->id;
            $searchType = $request->searchType;
            $searchType = ($searchType) && !empty($searchType) ? $searchType : '';
            $where = [];

            if ($searchType !== '') {
                array_push($where, " type = '" . $searchType . "'");
            }


            $sql = "SELECT DISTINCT id FROM parties";

            if (count($where) > 0) {
                $sql = $sql . " WHERE " . implode(" AND ", $where);
            }


            $res = DB::select($sql);
            $total = count($res);
            $per_page = env("PAGINATION", 10);
            $current_page = $request->input("page") ?? 1;
            $starting_point = ($current_page * $per_page) - $per_page;
            $sql .= " Limit $per_page OFFSET $starting_point";
            $final_result = DB::select($sql);

            $i = 0;
            foreach ($final_result as $result) {
                $party = Party::find($result->id);

                $pageRequest = PageRequest::where('partyId', $party->id)->where('requestedBy', $userId)->first();
                if ($pageRequest) {
                    $final_result[$i]->name = $party->name;
                    $final_result[$i]->logo = $party->logo;
                    $final_result[$i]->type = $party->type;
                    $final_result[$i]->followercount = $party->followercount;
                    $final_result[$i]->voluntercount = $party->voluntercount;
                    $final_result[$i]->stateCode = $party->getStateCode();
                    $final_result[$i]->requestType = $pageRequest->requestType;
                    $final_result[$i]->status = $pageRequest->status;
                    $i++;
                } else {
                    $final_result = [];
                }
            }

            $list = new CustomPaginationPageRequest($final_result, $total, $per_page, $current_page, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return response()->json(['status' => 'OK', 'result' => $list], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString(), $e->getCode());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }


    }
    /**
     * @OA\Post(
     *     path="/api/acceptRequestFromParty",
     *     summary="acceptRequestFromParty",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId"},
     *         @OA\Property(property="partyId", type="string", example="partyId"),
     *        
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
    public function acceptRequestFromParty(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $leaderId = Auth::user()->id;
            $newPartyLogin = [
                'partyId' => $partyId,
                'leaderId' => $leaderId,
            ];
            PartyLogin::create($newPartyLogin);
            AssignPartyToLeaders::where('leaderId', $leaderId)->where('partyId', $partyId)->delete();

            $template = Action::getTemplate('Party Accept');
            $Leader = User::where("id", $leaderId)->first();
            $PartyLeaders = PartyLogin::where("partyId", $partyId)->get();
            $fullName = $Leader->getFullName();


            foreach ($PartyLeaders as $PartyLeader) {
                if ($template['type'] == "template") {
                    foreach ($template['data'] as $d) {
                        $content = str_replace(["{PartyLeaderName}", "{LeaderName}"], [$PartyLeader->user->firstName, $fullName], $d->content);
                        $mail['content'] = $content;
                        $mail['email'] = $PartyLeader->user->email;
                        $mail['subject'] = $d->subject;
                        $mail['fileName'] = "template";
                        $mail['cc'] = '';
                        if ($d->cc != null) {
                            $mail['cc'] = $d->cc;
                        }
                    }
                    Action::sendEmail($mail);
                }
            }

            /* ============================= sending push notification ============================== */
            $party = Party::where('id', $partyId)->first();
            $userId = $party->id;
            $replaceArray = ['{LeaderName}' => $fullName];
            $notificationType = 'party_accept';
            $UserType = 'Party';
            $getNotification = Action::getNotification('party', $notificationType);
            Action::createNotification($userId, $UserType, $party->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */




            return response()->json(['status' => 'OK', 'message' => "Request accepted"], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString(), $e->getCode());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/declineRequestFromParty",
     *     summary="declineRequestFromParty",
     *     tags={"LeaderProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId"},
     *         @OA\Property(property="partyId", type="string", example="partyId"),
     *        
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
    public function declineRequestFromParty(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $leaderId = Auth::user()->id;
            AssignPartyToLeaders::where('leaderId', $leaderId)->where('partyId', $partyId)->delete();

            $template = Action::getTemplate('Party Reject');
            $Leader = User::where("id", $leaderId)->first();
            $PartyLeaders = PartyLogin::where("partyId", $partyId)->get();
            $fullName = $Leader->getFullName();

            foreach ($PartyLeaders as $PartyLeader) {
                if ($template['type'] == "template") {
                    foreach ($template['data'] as $d) {
                        $content = str_replace(["{PartyLeaderName}", "{LeaderName}"], [$PartyLeader->user->firstName, $fullName], $d->content);
                        $mail['content'] = $content;
                        $mail['email'] = $PartyLeader->user->email;
                        $mail['subject'] = $d->subject;
                        $mail['fileName'] = "template";
                        $mail['cc'] = '';
                        if ($d->cc != null) {
                            $mail['cc'] = $d->cc;
                        }
                    }
                    Action::sendEmail($mail);
                }
            }

            /* ============================= sending push notification ============================== */
            $party = Party::where('id', $partyId)->first();
            $userId = $party->id;
            $replaceArray = ['{LeaderName}' => $fullName];
            $notificationType = 'party_reject';
            $UserType = 'Party';
            $getNotification = Action::getNotification('party', $notificationType);
            Action::createNotification($userId, $UserType, $party->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */

            return response()->json(['status' => 'OK', 'message' => "Request decline"], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString(), $e->getCode());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    public function destroy(string $id)
    {
        //
    }


       /**
     * @OA\Delete(
     *     path="/api/cancelRequest/{id}",
     *     summary="Delete cancelRequest by id",
     *     tags={"LeaderProfile"},
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
    public function cancelRequest($id)
    {
        try {
         PageRequest::where('id', $id)->delete();
         return response()->json(['status' => 'OK', 'message' => "Request canceled"], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }
}