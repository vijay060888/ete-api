<?php

namespace App\Helpers;
use App\Models\PostByLeader;
use App\Models\PostByParty;
use Carbon\Carbon;

class Calculation
{
   
   
    public static function calculatePostFrequency($leaderId)
    {
        $leaderPosts = PostByLeader::where('leaderId', $leaderId)
            ->latest('createdAt')
            ->take(5)
            ->get();

        if ($leaderPosts->count() < 1) {
            return 'N/A';
        }

        $timeDifferences = [];
        $currentDateTime = Carbon::now();

        foreach ($leaderPosts as $post) {
            $postDateTime = Carbon::parse($post->createdAt);
            $timeDifference = $postDateTime->diffForHumans($currentDateTime);
            $timeDifferences[] = $postDateTime->diffInSeconds($currentDateTime);
        }

        $averageTimeDifference = array_sum($timeDifferences) / count($timeDifferences);

        if ($averageTimeDifference >= 86400) {
            $frequency = round($averageTimeDifference / 86400) . ' days';
        } elseif ($averageTimeDifference >= 3600) {
            $frequency = round($averageTimeDifference / 3600) . ' hours';
        } else {
            $frequency = round($averageTimeDifference / 60) . ' minutes';
        }

        return $frequency;
    }

    public static function calculatePostFrequencyParty($partyId)
    {
        $partyPosts = PostByParty::where('leaderId', $partyId)
            ->latest('createdAt')
            ->take(5)
            ->get();

        if ($partyPosts->count() < 1) {
            return 'N/A';
        }

        $timeDifferences = [];
        $currentDateTime = Carbon::now();

        foreach ($partyPosts as $post) {
            $postDateTime = Carbon::parse($post->createdAt);
            $timeDifference = $postDateTime->diffForHumans($currentDateTime);
            $timeDifferences[] = $postDateTime->diffInSeconds($currentDateTime);
        }

        $averageTimeDifference = array_sum($timeDifferences) / count($timeDifferences);

        if ($averageTimeDifference >= 86400) {
            $frequency = round($averageTimeDifference / 86400) . ' days';
        } elseif ($averageTimeDifference >= 3600) {
            $frequency = round($averageTimeDifference / 3600) . ' hours';
        } else {
            $frequency = round($averageTimeDifference / 60) . ' minutes';
        }

        return $frequency;
    }

}
