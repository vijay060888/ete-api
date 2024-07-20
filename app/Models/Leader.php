<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leader extends Model
{
    use HasFactory,HasUuids;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    protected $fillable=[
        'id',
        'leadersId',
        'token',
        'officialPage',
        'descriptionShort',
        'descriptionBrief',
        'about',
        'file',
        'leaderMission',
        'leaderVision',
        'officeAddress',
        'leaderBiography',
        'phoneNumber2',
        'timeLine',
        'contact',
        'social',
        'leaderMinistry',
        'backgroundImage',
        'leaderPartyRole',
        'leaderPartyRoleLevel',
        'leaderConsituencyId'
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'leadersId', 'id');
    }
    public function assignLeader()
    {
        return $this->hasMany(AssignLeader::class, 'leaderId', 'leaderId');
    }

    public function checkIfAssignRequest()
    {
        $assignLeader = $this->assignLeader()->first();

        if ($assignLeader) {
            $party = Party::where('id', $assignLeader->partyId)->first();

            if ($party) {
                return [
                    'partyName' => $party->name,
                    'partyLogo' => $party->logo,
                    'partyType' => $party->type,
                ];
            }
        }

        return null;
    }
    public function leaderDetails()
    {
        return $this->belongsTo(UserDetails::class, 'leadersId', 'userId');
    }

    public function leaderMinistry()
    {
        return $this->belongsTo(LeaderMinistry::class, 'leadersId', 'leaderId');
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class, 'leadersId', 'leaderMinistry');
    }
    public function getLeaderCoreParty()
    {
        return $this->belongsTo(LeaderCoreParty::class, 'leadersId', 'leaderId');
    }
}
