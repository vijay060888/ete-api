<?php

namespace App\Models;

use Auth;
use DB;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class Party extends Authenticatable implements JWTSubject
{
    use HasFactory, HasUuids;
    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';


    protected $fillable = [
        'id',
        'userName',
        'password',
        'timelineYear',
        'timelineHeading',
        'name',
        'nameAbbrevation',
        'logo',
        'officialPage',
        'phoneNumber2',
        'file',
        'descriptionShort',
        'descriptionBrief',
        'type',
        'hashTags',
        'parentPartyId',
        'about',
        'timeLine',
        'contact',
        'social',
        'createdBy',
        'updatedBy',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function getPartyNamesByState()
    {
        $partyNames = [];

        if ($this->id !== null) {
            $partyStates = PartyState::where('parentPartyId', $this->id)->select('stateId')->paginate(env('PAGINATION_PER_PAGE', 10));

            if (!$partyStates->isEmpty()) {
                $stateIds = $partyStates->pluck('stateId')->toArray();
                $partyIds = PartyState::whereIn('stateId', $stateIds)
                    ->pluck('partyId');
                $partyNames = Party::whereIn('id', $partyIds)
                    ->select('name', 'followercount', 'voluntercount', 'logo')
                    ->get();
            }
        }

        return $partyNames;
    }



    public function getPartyNamesByStateAndAssembly()
    {
        $partyNames = [];
        $i = 0;
        if ($this->id != null) {
            $partyAssembly = AssemblyParty::where('parentPartyId', $this->id)->select('assemblyId', 'partyId')->paginate(env('PAGINATION_PER_PAGE', 10));
            if ($partyAssembly !== null) {
                foreach ($partyAssembly as $partyAssemblies) {
                    $partyDetails = Party::where('id', $partyAssemblies->partyId)->select('name', 'followercount', 'voluntercount', 'logo')->first();
                    if ($partyDetails !== null) {
                        $stateAssembly = StateAssembly::where('assemblyId', $partyAssemblies->assemblyId)->first();
                    }
                    $lokSabhaData = json_decode($partyDetails, true);
                    $state = State::where('id', $stateAssembly->stateId)->first();

                    $lokSabhaData['stateCode'] = $state->code;

                    $partyDetails = $lokSabhaData;
                    $partyNames[] = $partyDetails;

                }

                return $partyNames;
            }


        }
    }




    public function getLokSabhaNames()
    {
        $partyNames = [];

        if ($this->id !== null) {
            $partyLoksabha = LoksabhaParty::where('parentPartyId', $this->id)->select('loksabhaId', 'partyId')->paginate(env('PAGINATION_PER_PAGE', 10));
            foreach ($partyLoksabha as $partyLoksabhas) {
                $partyDetails = Party::where('id', $partyLoksabhas->partyId)->select('name', 'followercount', 'voluntercount', 'logo' ,'type')->first();
                if ($partyDetails !== null ) {
                    $stateLoksabha = StateLokSabha::where('loksabhaId', $partyLoksabhas->loksabhaId)->first();
                    $lokSabhaData = json_decode($partyDetails, true);
                    if( $stateLoksabha!='')
                    {
                    $state = State::where('id', $stateLoksabha->stateId)->first();
                    $lokSabhaData['stateCode'] = $state->code;

                    }

                    $partyDetails = $lokSabhaData;
                    $partyNames[] = $partyDetails;
                }
            }
        }

        return $partyNames;


    }
    public function getAboutDetails()
    {

        if ($this->id != null) {
            $about = AboutParty::where('partyId', $this->id)->select('about', 'vision', 'mission')->first();
            if ($about != '') {
                return $about;
            }
        }
        return '';
    }

    public function getPartyTimeLine()
    {
        if ($this->id != null) {
            $timeline = PartyTimeline::where('partyId', $this->id)->first();
            if ($timeline != '') {
                return $timeline;
            }
        }
        return '';
    }

    public function getPartyContact()
    {
        if ($this->id != null) {
            $contactDetails = PartyContactDetails::where('partyId', $this->id)->first();
            if ($contactDetails != '') {
                return $contactDetails;
            }
        }
        return '';
    }

    public function getPartySocial()
    {
        if ($this->id != null) {
            $partyStates = PartySocial::where('partyId', $this->id)->first();
            if ($partyStates != '') {
                return $partyStates;
            }
        }
        return '';
    }


    public function getCreatePageRequest()
    {
        if ($this->id != null) {
            $userDetails = [];
            $parentpartyId = $this->id;

            $partyState = PartyState::where('partyId', $this->id)->first();
            $pageRequest = PageRequest::join('parties', 'page_requests.partyId', '=', 'parties.id')
                ->where(function ($query) use ($parentpartyId) {
                    $query->where('parties.parentPartyId', '=', $parentpartyId)
                        ->where('page_requests.requestType', '=', 'Create')
                        ->orWhere('parties.id', '=', $parentpartyId);
                })
                ->select('page_requests.id as requestedId', 'parties.*', 'page_requests.*')
                ->paginate(env('PAGINATION_PER_PAGE', 10));
            foreach ($pageRequest as $userList) {
                $user = User::where('id', $userList->requestedBy)->select('id as userId', 'firstName', 'lastName')->first();
                $moreDetails = UserDetails::where('userId', $userList->requestedBy)->first();
                $party = Party::where('id', $userList->partyId)->first();
                if ($userList->assemblyId != '') {
                    $assemblyName = AssemblyConsituency::where('id', $userList->assemblyId)->first();
                    $partyName = $assemblyName->name;
                }
                if ($userList->stateId != '') {
                    $stateName = State::where('id', $userList->stateId)->first();
                    $partyName = $stateName->name;
                }
                if ($userList->loksabhaId != '') {
                    $loksabhaName = LokSabhaConsituency::where('id', $userList->lokasabhaId)->first();
                    $partyName = $loksabhaName->name;
                }
                if ($userList->loksabhaId == '' && $userList->stateId == '' && $userList->assemblyId == '') {
                    $party = Party::where('id', $userList->partyId)->first();
                    $partyName = $party->name;
                }
                if ($userList->requestType == "Create") {
                    $message = "Requested to Create page for " . $party->name . "-" . $partyName;

                } else {
                    if (PartyLogin::where('userId', $userList->userId)->exists()) {
                        $user->status = true;
                    } else {
                        $user->status = false;
                    }
                    $message = "Requested to grant access for " . $partyName;
                }
                if ($user && $moreDetails) {
                    $user->profilePicture = $moreDetails->profileImage;
                    $userDetails[] = $user;
                    $user->message = $message;
                    $user->id = $party->id;
                    $user->partyId = $party->id;
                    $user->requestId = $userList->requestedId;
                    $user->requestType = $userList->requestType;
                    $user->allowedOrDenied = $userList->status;
                }
            }
            return $userDetails;

        }
    }

    public function getCreatePageRequestOnlyCreate()
    {
        $createPageRequest = $this->getCreatePageRequest();

        $createRequests = array_filter($createPageRequest, function ($user) {
            return $user->requestType === "Create";
        });

       $createRequests = array_values($createRequests);

        return $createRequests;
    }
    public function getAccessPageRequestOnlyAccess()
    {
        $accessPageRequest = $this->getCreatePageRequest();

        $accessPageRequest = array_filter($accessPageRequest, function ($user) {
            return $user->requestType === "Access";
        });

    $accessPageRequest = array_values($accessPageRequest);

    return $accessPageRequest; 
    }
    public function followers()
    {
        return $this->hasMany(PartyFollowers::class, 'partyId', 'id');
    }
    public function states()
    {
        return $this->belongsToMany(State::class, 'party_states', 'partyId', 'stateId');
    }
    public function getStateCode()
    {
        $stateCode = '';

        $party = Party::where('id', $this->id)->first();

        if (!$party) {
            return $stateCode; 
        }

        if ($party->type == "State") {
            $partyState = PartyState::where('partyId', $this->id)->get();

            if ($partyState->isNotEmpty()) {
                foreach ($partyState as $partyStates) {
                    $state = State::where('id', $partyStates->stateId)->first();

                    if ($state) {
                        $stateCode = $state->code;
                    }
                }
            }
        } elseif ($party->type == "Assembly") {
            $assemblyParty = AssemblyParty::where('partyId', $this->id)->first();

            if ($assemblyParty) {
                $stateAssembly = StateAssembly::where('assemblyId', $assemblyParty->assemblyId)->first();

                if ($stateAssembly) {
                    $state = State::where('id', $stateAssembly->stateId)->first();

                    if ($state) {
                        $stateCode = $state->code;
                    }
                }
            }
        } elseif ($party->type == "LokSabha") {
            $lokSabha = LoksabhaParty::where('partyId', $this->id)->first();

            if ($lokSabha) {
                $stateAssembly = StateLokSabha::where('loksabhaId', $lokSabha->loksabhaId)->first();

                if ($stateAssembly) {
                    $state = State::where('id', $stateAssembly->stateId)->first();

                    if ($state) {
                        $stateCode = $state->code;
                    }
                }
            }
        }

        return $stateCode;
    }
    public function changeRequestToLeaderForAccess()
    {
        $partyId = $this->id;
        $party = Party::where('id', $partyId)->first();
        $leaderCoreParty = LeaderCoreParty::where('corePartyId', $party->id)->where('leaderId', Auth::user()->id)->first();

        $leaderUsers = User::join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->where('roles.name', 'Leader')
            ->select('users.firstName', 'users.lastName', 'user_details.profileImage', 'users.id')
            ->get();

        $partyIds = PartyLogin::where('partyId', $this->id)->pluck('userId')->toArray();

        $filteredLeaderUsers = $leaderUsers->filter(function ($leaderUser) use ($partyIds) {
            $assigned = false; 
            if (AssignPartyToLeaders::where('leaderId', $leaderUser->id)
                ->where('partyId', $this->id)
                ->exists()) {
                $assigned = true; 
            }
        
            $leaderUser->requested = $assigned;
        
            return !in_array($leaderUser->id, $partyIds);
        
        });

        
    
        $filteredLeaderUsers = $filteredLeaderUsers->values();

        return $filteredLeaderUsers;

    }
    public function party_logins()
    {
        return $this->hasMany(PartyLogin::class, 'partyId', 'id');
    }
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}