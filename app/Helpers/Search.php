<?php

namespace App\Helpers;

use App\Models\AssemblyConsituency;
use App\Models\LeaderFollowers;
use App\Models\LokSabhaConsituency;
use App\Models\Party;
use App\Models\PartyFollowers;
use App\Models\PostByLeader;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserDetails;
use Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;

class Search
{
    public static function searchParty($keyword)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);
        $partySearch = Party::where('name', 'ILIKE', "%$keyword%")->select('name', 'logo', 'followercount', 'voluntercount', 'id', 'type')->paginate($perPage);

        foreach ($partySearch as $party) {
            $stateCode = $party->getStateCode();
            $party->stateCode = $stateCode;
        }
        return $partySearch;

    }

    public static function searchPartywithAdminAccess($keyword)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);
        return $keyword;
        $userId = Auth::user()->id;
        $partySearch = Party::where('name', 'ILIKE', "%$keyword%")
            ->select('name', 'logo', 'followercount', 'voluntercount', 'id', 'type')
            ->whereHas('party_logins', function ($query) use ($userId) {
                $query->where('userId', $userId);
            })
            ->whereHas('party_logins', function ($query) {
                $query->whereColumn('partyId', 'parties.id');
            })
            ->paginate($perPage);


        foreach ($partySearch as $party) {
            $stateCode = $party->getStateCode();
            $party->stateCode = $stateCode;
        }
        return $partySearch;

    }

    public static function searchLeaderYouFollow($keyword)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);

        $leaderFollowers = LeaderFollowers::where('followerId', Auth::user()->id)
            ->join('users', 'leader_followers.leaderId', '=', 'users.id')
            ->join('user_details', 'users.id', '=', 'user_details.userId')
            ->where('users.firstName', 'LIKE', "$keyword")
            ->select(
                'leader_followers.id as leaderFollowingId',
                'users.id as leaderId',
                'users.firstName',
                'users.lastName',
                'user_details.profileImage'
            )
            ->paginate($perPage)
            ->map(function ($follower) {
                return $follower;
            });
        return response()->json(['status' => 'success', 'leader' => $leaderFollowers], 200);
    }
    public static function searchPartyYouFollow($keyword)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);
        $partyFollowers = PartyFollowers::where('followerId', Auth::user()->id)
            ->with(['party:id,name,logo'])
            ->where(function ($query) use ($keyword) {
                if (!empty($keyword)) {
                    $query->whereHas('party', function ($subQuery) use ($keyword) {
                        $subQuery->where('name', 'like', "%$keyword%");
                    });
                }
            })
            ->paginate($perPage)
            ->map(function ($follower) {
                $stateCode = !empty($follower->party->getStateCode()) ? $follower->party->getStateCode() : '';
                return [
                    'followingId' => $follower->id,
                    'partyId' => $follower->party->id,
                    'name' => $follower->party->name,
                    'logo' => $follower->party->logo,
                    'stateCode' => $stateCode,
                ];
            });


        return response()->json(['status' => 'success', 'leader' => $partyFollowers], 200);
    }

  public static function constituencySearch($currentPage, $keyword)
{
    $assembly = AssemblyConsituency::where('name', 'ILIKE', "%$keyword%")
        ->select('name', 'id as id','logo as profileImage')
        ->orderByRaw("CASE 
                            WHEN \"name\" ILIKE '$keyword%' THEN 1 -- Starts with keyword
                            WHEN \"name\" ILIKE '%$keyword%' THEN 2 -- Contains keyword
                            ELSE 3 
                        END")
        ->get();

    $assembly = $assembly->map(function ($item) {
        $item->type = 'Constituency';
        return $item;
    });

    $loksabha = LokSabhaConsituency::where('name', 'ILIKE', "%$keyword%")
        ->select('name', 'id as id','logo as profileImage')
        ->orderByRaw("CASE 
                            WHEN \"name\" ILIKE '$keyword%' THEN 1 -- Starts with keyword
                            WHEN \"name\" ILIKE '%$keyword%' THEN 2 -- Contains keyword
                            ELSE 3 
                        END")
        ->get();

    $loksabha = $loksabha->map(function ($item) {
        $item->type = 'Constituency';
        return $item;
    });

    $perPage = env('PAGINATION_PER_PAGE', 10);

    $mergedResults = $assembly->concat($loksabha);
    
    $mergedResults = array_values($mergedResults->toArray());

    $total = count($mergedResults);
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentPageResults = array_slice($mergedResults, ($currentPage - 1) * $perPage, $perPage);

    $paginatedResults = new LengthAwarePaginator($currentPageResults, $total, $perPage, $currentPage, [
        'path' => LengthAwarePaginator::resolveCurrentPath(),
    ]);

    return $paginatedResults;
}



    public static function partiesSearch($currentPage, $keyword)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);
        $parties = Party::where('name', 'ILIKE', "%$keyword%")
                            ->orWhere("nameAbbrevation", 'ILIKE', "%$keyword%")
                            ->select('id', 'name', 'nameAbbrevation', 'logo')
                            ->orderByRaw("CASE 
                                        WHEN \"name\" ILIKE '%$keyword%' THEN 1 
                                        WHEN \"nameAbbrevation\" ILIKE '%$keyword%' THEN 2 
                                        ELSE 3 
                                    END")
                            ->paginate($perPage);
        return $parties;
    }
    public static function leaderUserSearch($currentPage, $keyword)
{
    $perPage = env('PAGINATION_PER_PAGE', 10);
    $query = DB::table('users')
        ->join('user_details', 'users.id', '=', 'user_details.userId')
        ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
        ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->select(
            'users.id as leaderId',
            'users.firstName',
            'users.lastName',
            'user_details.profileImage',
            DB::raw("CASE 
                    WHEN users.\"firstName\" ILIKE '%$keyword%' THEN 1 
                    WHEN users.\"lastName\" ILIKE '%$keyword%' THEN 2 
                    ELSE 3 
                END AS keyword_order")
        )
        ->where('model_has_roles.model_type', '=', 'App\Models\User')
        ->where('roles.name', 'Leader')
        ->where(function ($query) use ($keyword) {
            $query->where('users.firstName', 'ILIKE', "%$keyword%")
                ->orWhere('users.lastName', 'ILIKE', "%$keyword%");
        })
        ->orderBy('keyword_order');

    $paginator = $query->paginate($perPage);

    $transformedUsers = $paginator->map(function ($user) {
        $name = $user->firstName . " " . $user->lastName;
        return [
            'id' => $user->leaderId,
            'name' => $name,
            'profileImage' => $user->profileImage
        ];
    });

    return [
        'status' => 'success',
        'message' => 'Search Result',
        'current_page' => $paginator->currentPage(),
        'data' => $transformedUsers,
        'first_page_url' => $paginator->url(1),
        'from' => $paginator->firstItem(),
        'last_page' => $paginator->lastPage(),
        'last_page_url' => $paginator->url($paginator->lastPage()),
        'links' => $paginator->links(),
        'next_page_url' => $paginator->nextPageUrl(),
        'path' => $paginator->path(),
        'per_page' => $perPage,
        'prev_page_url' => $paginator->previousPageUrl(),
        'to' => $paginator->lastItem(),
        'total' => $paginator->total(),
    ];
}

}