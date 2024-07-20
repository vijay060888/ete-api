<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\StateDetails;
use App\Models\AssemblyDetails;
use App\Models\LoksabhaDetails;
use App\Models\AssemblyFollowerDetails;
use Illuminate\Support\Facades\Broadcast;

trait BroadcastCount
{
    /**
     * Broadcast the count of a given model.
     *
     * @param string $channelName
     * @param int $count
     * @param string|null $event
     * @return void
     */
    function insertOrUpdateUserstateForBroadCast($stateId, $gender, $dob, $increment) {
        $stateExists = StateDetails::where('stateId', $stateId)->where('gender', $gender)->first();
        if(!$stateExists) {
            $ageRangeArr = $this->updateAgeRangeKey(['10' => 0, '20'=> 0, '30'=> 0, '40'=> 0, '50' => 0, '60' => 0, '70'=> 0, '80'=> 0], $dob, $increment);
            StateDetails::create([
                'stateId' => $stateId,
                'gender' => $gender,
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => 1,
            ]);
        } else {
            $ageRangeArr = $this->updateAgeRangeKey(json_decode($stateExists->ageRange, true), $dob, $increment);
            $stateExists->update([
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => ($increment) ? ($stateExists->user_count + 1) : ($stateExists->user_count - 1),
            ]);
        }
        return true;
    }
    // function updateAgeRangeKey($ageRangeArr, $dob, $increment) {
    //     $birthdate = Carbon::parse($dob);
    //     $currentDate = Carbon::now();
    //     $userAge = $currentDate->diffInYears($birthdate);
    //     $AgeKey = floor($userAge / 10) * 10;
    //     if (isset($ageRangeArr[$AgeKey])) {
    //         $ageRangeArr[$AgeKey] = ($increment) ? ($ageRangeArr[$AgeKey] + 1) : ($ageRangeArr[$AgeKey] - 1);
    //     }        
    //     return $ageRangeArr;
    // }
    

    function insertOrUpdateUserAssemblyForBroadCast($assemblyId, $gender, $dob, $increment) {
        $assemblyExists = AssemblyDetails::where('assemblyId', $assemblyId)->where('gender', $gender)->first();
        if(!$assemblyExists && $increment) {
            $ageRangeArr = $this->updateAgeRangeKey(['10' => 0, '20'=> 0, '30'=> 0, '40'=> 0, '50' => 0, '60' => 0, '70'=> 0, '80'=> 0], $dob, $increment);
            AssemblyDetails::create([
                'assemblyId' => $assemblyId,
                'gender' => $gender,
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => 1,
            ]);
        } else if ($assemblyExists){
            $ageRangeArr = $this->updateAgeRangeKey(json_decode($assemblyExists->ageRange, true), $dob, $increment);
            $userCount = $assemblyExists->user_count;
            $assemblyExists->update([
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => ($increment) ? ($userCount + 1) : ($userCount - 1),
            ]);
        }
        return true;
    }



    function insertOrUpdateUserLokSabhaForBroadCast($lokSabhaId, $gender, $dob, $increment) {
        $lokSabhaData = LoksabhaDetails::where('loksabhaId', $lokSabhaId)->where('gender', $gender)->first();
        if(!$lokSabhaData && $increment) {
            $ageRangeArr = $this->updateAgeRangeKey(['10' => 0, '20'=> 0, '30'=> 0, '40'=> 0, '50' => 0, '60' => 0, '70'=> 0, '80'=> 0], $dob, $increment);
            LoksabhaDetails::create([
                'loksabhaId' => $lokSabhaId,
                'gender' => $gender,
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => 1,
            ]);
        } else if($lokSabhaData){
            $ageRangeArr = $this->updateAgeRangeKey(json_decode($lokSabhaData->ageRange, true), $dob, $increment);
            $userCount = $lokSabhaData->user_count;
            $lokSabhaData->update([
                'ageRange' => json_encode($ageRangeArr),
                'user_count' => ($increment) ? ($userCount + 1) : ($userCount - 1),
            ]);
        }
        return true;
    }

    function insertOrUpdateUserAssemblyFollowersForBroadCast($assemblyId, $gender, $dob, $increment, $following) {
        $assemblyExists = AssemblyFollowerDetails::where('assemblyId', $assemblyId)->where('gender', $gender)->first();
        if(!$assemblyExists) {
            if($following) {
                $ageRangeArr = $this->updateAgeRangeKey(['10' => 0, '20'=> 0, '30'=> 0, '40'=> 0, '50' => 0, '60' => 0, '70'=> 0, '80'=> 0], $dob, $increment);
                AssemblyFollowerDetails::create([
                    'assemblyId' => $assemblyId,
                    'gender' => $gender,
                    'ageRange' => json_encode($ageRangeArr),
                    'follower_count' => 1,
                ]);
            } else {
                return true;
            }
        } else {
            $ageRangeArr = $this->updateAgeRangeKey(json_decode($assemblyExists->ageRange, true), $dob, $increment);
            $userCount = $assemblyExists->follower_count;
            $assemblyExists->update([
                'ageRange' => json_encode($ageRangeArr),
                'follower_count' => ($increment) ? ($userCount + 1) : (($userCount - 1 < 0) ? 0 : $userCount - 1),
            ]);
        }
        return true;
    }

    function updateAgeRangeKey($ageRangeArr, $dob, $increment) {
        $birthdate = Carbon::parse($dob);
        $currentDate = Carbon::now();
        $userAge = $currentDate->diffInYears($birthdate);
        $AgeKey = floor($userAge / 10) * 10;
        if (isset($ageRangeArr[$AgeKey])) {
            $ageRangeArr[$AgeKey] = ($increment) ? ($ageRangeArr[$AgeKey] + 1) : (($ageRangeArr[$AgeKey] - 1 < 0) ? 0 : $ageRangeArr[$AgeKey] - 1);
        }        
        return $ageRangeArr;
    }
    
}
