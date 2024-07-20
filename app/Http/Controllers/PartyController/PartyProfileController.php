<?php

namespace App\Http\Controllers\PartyController;

use App\Helpers\Action;
use App\Helpers\PartyProfileDetails;
use App\Models\AssignPartyToLeaders;
use App\Models\LokSabhaConsituency;
use App\Models\LoksabhaParty;
use App\Models\PageRequest;
use App\Models\PartyLogin;
use App\Models\User;
use App\Models\UserDetails;
use Illuminate\Support\Facades\Validator;
use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\AboutParty;
use App\Models\AssemblyConsituency;
use App\Models\AssemblyParty;
use App\Models\Party;
use App\Models\PartyContactDetails;
use App\Models\PartySocial;
use App\Models\PartyState;
use App\Models\PartyTimeline;
use App\Models\State;
use Auth;
use Illuminate\Http\Request;

class PartyProfileController extends Controller
{


    public function index(Request $request)
    {
        try {
            $partyId = Auth::user()->partyId;

            $partyId = $request->id;

            if ($partyId == '') {
                return response()->json(['status' => 'error', 'message' => "Missing PartyId"], 404);
            }
            $party = Party::find($partyId);

            $party = Party::where('id', $partyId)
                ->select('id', 'file', 'social', 'logo', 'backgroundImage', 'nameAbbrevation', 'name', 'followercount', 'voluntercount')
                ->first();

            if ($party) {
                $party['statePages'] = $party->getPartyNamesByState() ?? [];
                $party['assemblyPages'] = $party->getPartyNamesByStateAndAssembly() ?? [];
                $party['loksabhaPages'] = $party->getLokSabhaNames() ?? [];

                //about
                $party['about'] = $party->getAboutDetails()->about ?? '';
                $party['vision'] = $party->getAboutDetails()->vision ?? '';
                $party['mission'] = $party->getAboutDetails()->mission ?? '';
                //timeline
                $party['timelineYear'] = $party->getPartyTimeLine()->year ?? '';
                $party['timelineHeading'] = $party->getPartyTimeLine()->heading ?? '';
                $party['timelineDescriptions'] = $party->getPartyTimeLine()->descriptions ?? '';
                //Contact Details 
                $party['contactName'] = $party->getPartyContact()->contactName ?? '';
                $party['phoneNumber'] = $party->getPartyContact()->phoneNumber ?? '';
                $party['phoneNumber2'] = $party->getPartyContact()->phoneNumber ?? '';
                $party['email'] = $party->getPartyContact()->email ?? '';
                $party['officeAddress'] = $party->getPartyContact()->officeAddress ?? '';
                $party['social'] = !empty($party) ? json_decode($party->social) : [];

                // $party['social'] =PartySocial::where('partyId','9a2d85dd-15cf-4f6e-9e6d-4202ec2817ee')->select('option','value')->get();


            } else {
                $party = [
                    'id' => '',
                    'statePages' => [],
                    'loksabhaPages' => [],
                    'assemblyPages' => [],
                    'about' => '',
                    'vision' => '',
                    'mission' => '',
                    'timelineYear' => '',
                    'timelineHeading' => '',
                    'timelineDescriptions' => '',
                    'file' => '',
                    'phonenumber2' => '',
                    'phoneNumber' => '',
                    'email' => '',
                    'social' => [],
                    'logo' => '',
                    'backgroundImage' => '',
                    'nameAbbrevation' => '',
                    'name' => '',
                    'contactName' => '',
                ];
            }
            return response()->json(['status' => 'success', 'message' => 'Party Profile details', 'result' => $party], 200);
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
     *     path="/api/partyProfile",
     *     summary="Add new Party",
     *     tags={"PartyProfile"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={"partyId","name", "stateId", "assemblyId", "about", "vision", "mission", "timelineYear", "timelineHeading", "timelineDescriptions", "file", "phonenumber", "phonenumber2", "email", "officeaddress", "social"},
     *             @OA\Property(property="partyId", type="string", example="partyId"),
     *             @OA\Property(property="stateId", type="string", example="State ID"),
     *             @OA\Property(property="assemblyId", type="string", example="Assembly ID"),
     *              @OA\Property(property="loksabhaId", type="string", example="loksabha Id"),
     *             @OA\Property(property="about", type="string", example="About Party"),
     *             @OA\Property(property="vision", type="string", example="Vision"),
     *             @OA\Property(property="mission", type="string", example="Mission"),
     *             @OA\Property(property="timelineYear", type="string", example="Timeline Year"),
     *             @OA\Property(property="timelineHeading", type="string", example="Timeline Heading"),
     *             @OA\Property(property="timelineDescriptions", type="string", example="Timeline Descriptions"),
     *             @OA\Property(property="file", type="string", example="File"),
     *             @OA\Property(property="contactname", type="string", example="contact name"),
     *             @OA\Property(property="phonenumber", type="string", example="Phone Number"),
     *             @OA\Property(property="phonenumber2", type="string", example="Second Phone Number"),
     *             @OA\Property(property="email", type="string", example="Email"),
     *            @OA\Property(property="backgroundImage", type="string", example="backgroundimage"),
     *             @OA\Property(property="officeaddress", type="string", example="Office Address"),
     *            @OA\Property(property="logo", type="string", example="logo"),
     *           
     *             @OA\Property(property="social", type="string", example="Social media collection")
     *         ),
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

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'stateId' => 'required',
                'partyId' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Required field like partyId or stateId is missing'], 400);
            }
            $partyId = $request->input('partyId');
            $party = Party::where('id', $partyId)->first();
            $stateId = $request->input('stateId') ?? '';
            $assemblyId = $request->input('assemblyId') ?? '';
            $loksabhaId = $request->input('loksabhaId') ?? '';
            $aboutParty = $request->input('about') ?? '';
            $vision = $request->input('vision') ?? '';
            $mission = $request->input('mission') ?? '';
            $contactName = $request->input('contactname') ?? '';
            $timelineYear = $request->input('timelineYear') ?? '';
            $timelineHeading = $request->input('timelineHeading') ?? '';
            $timelineDescriptions = $request->input('timelineDescriptions') ?? '';
            $file = $request->input('file') ?? '';
            $phonenumber = $request->input('phonenumber') ?? '';
            $phonenumber2 = $request->input('phonenumber2') ?? '';
            $email = $request->input('email') ?? '';
            $officeaddress = $request->input('officeaddress') ?? '';
            $social = $request->input('social') ?? '';
            $backgroundImage = $request->input('backgroundImage') ?? '';
            $logo = $request->input('logo') ?? '';
            $partyAbbreveation = '';

            if ($stateId != '' && $assemblyId == '' && $loksabhaId == '') {
                $stateName = State::where('id', $stateId)->first();
                $partyName = $party->nameAbbrevation . '-' . $stateName->name;
                $partyAbbreveation = $stateName->code;
                $type = "State";
            }

            if ($loksabhaId != '') {
                $loksabhaName = LokSabhaConsituency::where('id', $loksabhaId)->first();
                $partyName = $party->nameAbbrevation . '-' . $loksabhaName->name;
                $partyAbbreveation = $loksabhaName->code;
                $type = "LokSabha";
            }
            if ($assemblyId != '') {
                $assemblyName = AssemblyConsituency::where('id', $assemblyId)->first();
                $partyName = $party->nameAbbrevation . '-' . $assemblyName->name;
                $partyAbbreveation = $assemblyName->code;
                $type = "Assembly";

            }


            if (Party::where('name', $partyName)->exists()) {
                return response()->json(['status' => 'error', 'message' => 'This Party state or assembly page already exists'], 400);
            }

            $partyData = [
                'name' => $partyName,
                'nameAbbrevation' => $party->nameAbbrevation . '-' . $partyAbbreveation,
                'logo' => $logo,
                'type' => $type,
                'backgroundImage' => $backgroundImage,
                'file' => $file,
            ];


            $newParties = Party::create($partyData);
            Party::where('id', $newParties->id)->update(['social' => $social]);

            $aboutData = [
                'partyId' => $newParties->id,
                'contactname' => $contactName,
                'about' => $aboutParty,
                'vision' => $vision,
                'mission' => $mission,
                'createdBy' => $party->id,
                'updatedBy' => $party->id
            ];

            $partyContact = [
                'partyId' => $newParties->id,
                'contactName' => $contactName,
                'phoneNumber' => $phonenumber,
                'phoneNumber2' => $phonenumber2,
                'email' => $email,
                'officeAddress' => $officeaddress,
                'createdBy' => $party->id,
                'updatedBy' => $party->id,
            ];


            $partyTimeline = [
                'partyId' => $newParties->id,
                'year' => $timelineYear,
                'heading' => $timelineHeading,
                'descriptions' => $timelineDescriptions,
                'createdBy' => $party->id,
                'updatedBy' => $party->id
            ];



            AboutParty::create($aboutData);
            PartyContactDetails::create($partyContact);
            PartyTimeline::create($partyTimeline);

            if ($stateId != '' && $assemblyId == '' && $loksabhaId == '') {
                PartyState::create(['stateId' => $stateId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
            }

            if ($loksabhaId != '' && $assemblyId == '') {
                LoksabhaParty::create(['loksabhaId' => $loksabhaId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
            }

            if ($assemblyId != '' && $loksabhaId == '') {
                AssemblyParty::create(['assemblyId' => $assemblyId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
            }

            Party::where('id', $newParties->id)->update(['parentPartyId' => $party->id]);


            return response()->json(['status' => 'success', 'message' => 'New Party Created'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * Display the specified resource.
     */

    /**
     * @OA\Get(
     *     path="/api/partyProfile/{id}",
     *     summary="Get Party Profile",
     *     tags={"PartyProfile"},
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
        try {
            // $leaders = User::role('Leader')->select('firstName', 'lastName', 'id')->limit(3)->get();

            $partyId = $id;
            $canEdit = "True";
            if ($partyId == '') {
                return response()->json(['status' => 'error', 'message' => "Missing PartyId"], 404);
            }
            $currentPage = request('page', 1);
            $party = PartyProfileDetails::getPartyDetails($currentPage,$partyId, $canEdit,null);
            return response()->json(['status' => 'success', 'message' => 'Party Profile details', 'result' => $party], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
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
     *
     * @OA\Put(
     *     path="/api/partyProfile/{id}",
     *     summary="Update partyProfile details",
     *     tags={"PartyProfile"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the partyProfile to update",
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             required={
     *                 "about",
     *                 "vision",
     *                 "mission",
     *                 "timelineYear",
     *                 "timelineHeading",
     *                 "timelineDescriptions",
     *                 "file",
     *                 "phonenumber",
     *                 "phonenumber2",
     *                 "email",
     *                 "officeaddress",
     *                 "social",
     *                 "backgroundImage"
     *             },
     *             @OA\Property(property="about", type="string", example="About Party"),
     *             @OA\Property(property="vision", type="string", example="Vision"),
     *             @OA\Property(property="mission", type="string", example="Mission"),
     *             @OA\Property(property="timelineYear", type="string", example="Timeline Year"),
     *             @OA\Property(property="timelineHeading", type="string", example="Timeline Heading"),
     *             @OA\Property(property="timelineDescriptions", type="string", example="Timeline Descriptions"),
     *             @OA\Property(property="file", type="string", example="File"),
     *             @OA\Property(property="phonenumber", type="string", example="Phone Number"),
     *             @OA\Property(property="phonenumber2", type="string", example="Second Phone Number"),
     *             @OA\Property(property="email", type="string", example="Email"),
     *             @OA\Property(property="contactname", type="string", example="contact name"),
     *             @OA\Property(property="backgroundImage", type="string", example="backgroundimage"),
     *             @OA\Property(property="officeaddress", type="string", example="Office Address"),
     *             @OA\Property(property="social", type="string", example="Social media collection"),
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
            $party = Party::find($id);
            if ($party != '') {
                $logo = $party != '' ? $request->input('logo') ?? $party->logo : null;
                $backgroundImage = $party != '' ? $request->input('backgroundImage') ?? $party->backgroundImage : null;
                $file = $party != '' ? $request->input('file') ?? $party->file : null;
                $social = $party != '' ? $request->input('social') ?? $party->social : [];


                Party::updateOrInsert(
                    ['id' => $id],
                    [
                        'logo' => $logo,
                        'backgroundImage' => $backgroundImage,
                        'file' => $file,
                        'social' => $social,
                    ]
                );

                $aboutParty = AboutParty::where('partyId', $id)->first();
                $about = $aboutParty != '' ? $request->input('about') ?? $aboutParty->about : null;
                $vision = $aboutParty != '' ? $request->input('vision') ?? $aboutParty->vision : null;
                $mission = $aboutParty != '' ? $request->input('mission') ?? $aboutParty->mission : null;


                AboutParty::updateOrInsert(
                    ['partyId' => $id],
                    [
                        'id' => \DB::raw('gen_random_uuid()'),
                        'about' => $about,
                        'vision' => $vision,
                        'mission' => $mission,
                        'createdBy' => Auth::user()->partyId,
                        'updatedBy' => Auth::user()->partyId
                    ]
                );

                $partyContact = PartyContactDetails::where('partyId', $id)->first();
                $contactName = $partyContact != '' ? $request->input('contactName') ?? $partyContact->contactname : null;
                $phonenumber = $partyContact != '' ? $request->input('phoneNumber') ?? $partyContact->phoneNumber : null;
                $phonenumber2 = $partyContact != '' ? $request->input('phoneNumber2') ?? $partyContact->phoneNumber2 : null;
                $email = $partyContact != '' ? $request->input('email') ?? $partyContact->email : null;
                $officeaddress = $partyContact != '' ? $request->input('officeAddress') ?? $partyContact->officeaddress : null;

                PartyContactDetails::updateOrInsert(
                    ['partyId' => $id],
                    [
                        'id' => \DB::raw('gen_random_uuid()'),
                        'phoneNumber' => $phonenumber,
                        'phoneNumber2' => $phonenumber2,
                        'email' => $email,
                        'contactName' => $contactName,
                        'officeAddress' => $officeaddress,
                        'createdBy' => Auth::user()->partyId,
                        'updatedBy' => Auth::user()->partyId
                    ]
                );


                $partyTimeline = PartyTimeline::where('partyId', $id)->first();


                $timelineYear = $partyTimeline != '' ? $request->input('timelineYear') ?? $partyTimeline->year : null;
                $timelineHeading = $partyTimeline != '' ? $request->input('timelineHeading') ?? $partyTimeline->heading : null;
                $timelineDescriptions = $partyTimeline != '' ? $request->input('timelineDescriptions') ?? $partyTimeline->descriptions : null;

                PartyTimeline::updateOrInsert(
                    ['partyId' => $id],
                    [
                        'id' => \DB::raw('gen_random_uuid()'),
                        'year' => $timelineYear,
                        'heading' => $timelineHeading,
                        'descriptions' => $timelineDescriptions,
                        'createdBy' => Auth::user()->partyId,
                        'updatedBy' => Auth::user()->partyId
                    ]
                );

                return response()->json(['status' => 'success', 'message' => 'Party Profile update successfully'], 200);

            } else {
                return response()->json(['status' => 'error', 'message' => 'Something went wrong'], 400);

            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    /**
     * @OA\Get(
     *     path="/api/checkPartyAccess",
     *     summary="Fetch all party Access",
     *     tags={"PartyProfile"},
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
    public function checkPartyAccess()
    {
        try {
            $keyword = request('keyword');
            $party = PartyLogin::where('userId', Auth::user()->id)->get();
    
            $partyNames = [];
    
            if (count($party) > 0) {
                foreach ($party as $parties) {
                    $partyname = Party::where('id', $parties->partyId)
                        ->select('name', 'id as partyId', 'logo')
                        ->where('name', 'ILIKE', "%$keyword%")
                        ->first();
    
                    // Only add to the array if $partyname is not null
                    if ($partyname !== null) {
                        $partyNames[] = $partyname;
                    }
                }
            }
    
            return response()->json(['status' => 'success', 'message' => 'List of parties', 'result' => array_values($partyNames)], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    


    /**
     * @OA\Post(
     *     path="/api/createPartyFromRequest",
     *     summary="Create party from request",
     *     tags={"PartyProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"requestId"},
     *         @OA\Property(property="requestId", type="string", example="requestId"),
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
    public function createPartyFromRequest(Request $request)
    {
        try {
            $requestId = $request->requestId;
            $pageRequest = PageRequest::where('id', $requestId)->first();
            if ($pageRequest != '') {
                $stateId = $pageRequest->stateId;
                $assemblyId = $pageRequest->assemblyId;
                $loksabhaId = $pageRequest->loksabhaId;
                $party = Party::where('id', $pageRequest->partyId)->first();

                if ($stateId != '' && $assemblyId == '' && $loksabhaId == '') {
                    $stateName = State::where('id', $stateId)->first();
                    $partyName = $party->nameAbbrevation . '-' . $stateName->name;
                    $partyAbbreveation = $stateName->code;
                    $type = "State";

                }

                if ($loksabhaId != '') {
                    $loksabhaName = LokSabhaConsituency::where('id', $loksabhaId)->first();
                    $partyName = $party->nameAbbrevation . '-' . $loksabhaName->name;
                    $partyAbbreveation = $loksabhaName->code;
                    $type = "LokSabha";

                }
                if ($assemblyId != '') {
                    $assemblyName = AssemblyConsituency::where('id', $assemblyId)->first();
                    $partyName = $party->nameAbbrevation . '-' . $assemblyName->name;
                    $partyAbbreveation = $assemblyName->code;
                    $type = "Assembly";

                }
                if (Party::where('name', $partyName)->exists()) {
                    return response()->json(['status' => 'error', 'message' => 'This Party state or consituency page already exists'], 400);
                }

                $partyData = [
                    'name' => $partyName,
                    'nameAbbrevation' => $party->nameAbbrevation . '-' . $partyAbbreveation,
                    'logo' => $party->logo,
                    'type' => $type,
                    'backgroundImage' => $party->backgroundImage,
                ];
                $newParties = Party::create($partyData);

                if ($stateId != '' && $assemblyId == '' && $loksabhaId == '') {
                    PartyState::create(['stateId' => $stateId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
                }

                if ($loksabhaId != '' && $assemblyId == '') {
                    LoksabhaParty::create(['loksabhaId' => $loksabhaId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
                }

                if ($assemblyId != '' && $loksabhaId == '') {
                    AssemblyParty::create(['assemblyId' => $assemblyId, 'partyId' => $newParties->id, 'parentPartyId' => $party->id]);
                }

                PageRequest::where('id', $requestId)->delete();
                return response()->json(['status' => 'success', 'message' => 'New Party Created from Request'], 200);
            } else {
                return response()->json(['status' => 'error', 'message' => 'Something Went Wrong'], 200);

            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }
    /**
     * @OA\Post(
     *     path="/api/allowPartyPageAccess",
     *     summary="allowPartyPageAccess",
     *     tags={"PartyProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"requestId"},
     *         @OA\Property(property="requestId", type="string", example="requestId"),
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
    public function allowPageAccess(Request $request)
    {
        $requestId = $request->requestId;
        $status = $request->status;
        $pageRequest = PageRequest::where('id', $requestId)->first();

        
        if ($pageRequest != '') {
            if ($request->status == 'true') {
                $createAccess = [
                    'userId' => $pageRequest->requestedBy,
                    'partyId' => $pageRequest->partyId,
                ];
                PartyLogin::create($createAccess);
                $pageRequest->status = 'Allowed';
                $pageRequest->save();

            /* ============================= sending email ============================== */
            $template = Action::getTemplate('Page Accept');
            $User = User::where("id", $pageRequest->requestedBy)->first();
            $Party = Party::where("id",$pageRequest->partyId)->first();
            $fullName = $User->getFullName();
            if($template['type']=="template"){
                foreach($template['data'] as $d){
                    $content = str_replace(["{LeaderName}", "{partyName}"], [$fullName, $Party->name], $d->content);
                    $mail['content']=$content;
                    $mail['email']=$User->email;
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
            $userId = $User->userDetails->userId;
            $replaceArray = ['{PartyName}' => $Party->name];
            $notificationType = 'page_accept';
            $UserType = 'userLeader';
            $getNotification = Action::getNotification('party', $notificationType);
            Action::createNotification($userId, $UserType, $User->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
            /* ============================= sending push notification ============================== */



            } else {
                PartyLogin::where('userId', $pageRequest->userId)->where('partyId', $pageRequest->partyId)->delete();
            }

        }
        if ($status == 'true') {
            return response()->json(['status' => 'success', 'message' => 'Access granted'], 200);
        } else {
            return response()->json(['status' => 'success', 'message' => 'Access denied'], 200);
        }

    }

    /**
     * @OA\Post(
     *     path="/api/deniedPartyPageAccess",
     *     summary="allowPartyPageAccess",
     *     tags={"PartyProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"requestId"},
     *         @OA\Property(property="requestId", type="string", example="requestId"),
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
    public function deniedPageAccess(Request $request)
    {
        $requestId = $request->requestId;
        $pageRequest = PageRequest::where('id', $requestId)->first();
        $pageRequest->status = "Denied";
        $pageRequest->save();
         /* ============================= sending email ============================== */
         $template = Action::getTemplate('Page Reject');
         $User = User::where("id", $pageRequest->requestedBy)->first();
         $Party = Party::where("id",$pageRequest->partyId)->first();
         $fullName = $User->getFullName();
         if($template['type']=="template"){
             foreach($template['data'] as $d){
                 $content = str_replace(["{LeaderName}", "{partyName}"], [$fullName, $Party->name], $d->content);
                 $mail['content']=$content;
                 $mail['email']=$User->email;
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
        $userId = $User->userDetails->userId;
        $replaceArray = ['{PartyName}' => $Party->name];
        $notificationType = 'page_reject';
        $UserType = 'Leader';
        $getNotification = Action::getNotification('userLeader', $notificationType);
        Action::createNotification($userId, $UserType, $User->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
        /* ============================= sending push notification ============================== */

        return response()->json(['status' => 'success', 'message' => 'Access Denied'], 200);
    }
    public function destroy(string $id)
    {
        //
    }
       /**
     * @OA\Post(
     *     path="/api/assignLeaderToParty",
     *     summary="assignLeaderToParty",
     *     tags={"PartyProfile"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"partyId","leaderId"},
     *         @OA\Property(property="partyId", type="string", example="partyId"),
     *         @OA\Property(property="leaderId", type="string", example="leaderId"),
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
    public function assignLeaderToParty(Request $request)
    {
        $partyId = $request->partyId;
        $leaderId = $request->leaderId;
        $assignPartyToLeader = [
            "leaderId" => $leaderId,
            "partyId" => $partyId,
            "status" => "Pending",
            "createdBy" => Auth::user()->id,
            "updatedBy" => Auth::user()->id
        ];

        $existingRecord = AssignPartyToLeaders::where('partyId', $partyId)
            ->where('leaderId', $leaderId)
            ->first();

        if ($existingRecord) {
            return response()->json(['status'=>'error', 'message' => 'This request already created'], 400);
        } else {
            AssignPartyToLeaders::create($assignPartyToLeader);

            /* ============================= sending email ============================== */
            $template = Action::getTemplate('Party Request');
            $Leader = User::where("id", $leaderId)->first();
            $Party = Party::where("id",$partyId)->first();
            $fullName = $Leader->getFullName();
            if($template['type']=="template"){
                foreach($template['data'] as $d){
                    $content = str_replace(["{LeaderName}", "{partyName}"], [$fullName, $Party->name], $d->content);
                    $mail['content']=$content;
                    $mail['email']=$Leader->email;
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
                      $userId = $Leader->leaderDetails->userId;
                      $replaceArray = ['{partyName}' => $Party->name];
                      $notificationType = 'page_create';
                      $UserType = 'party';
                      $getNotification = Action::getNotification('userLeader', $notificationType);
                      Action::createNotification($userId, $UserType, $Leader->id, str_replace(array_keys($replaceArray), array_values($replaceArray), $getNotification));
                /* ============================= sending push notification ============================== */


            return response()->json(['status'=>'success', 'message' => 'Record created successfully'], 200);
        }
    }
}