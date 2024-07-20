<?php

namespace App\Http\Controllers\UserActivity;

use App\Helpers\FetchAllPost;
use App\Helpers\LogActivity;
use App\Helpers\OptimizeFetchPost;
use App\Http\Controllers\Controller;
use App\Models\TimeSpendDuration;
use App\Models\UserLogActivity;
use Auth;
use Crypt;
use DateTime;
use DB;
use Illuminate\Http\Request;

class UserActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/logactivity",
     *     summary="Fetch user Activity",
     *     tags={"User Activity"},
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
            $userId = Auth::user()->id;
            $deviceId = request('deviceId');
            $userActivity = UserLogActivity::where("userId", $userId)->whereNot('deviceId', $deviceId)->get();
            $mappedUserActivity = $userActivity->map(function ($activity) {
                $dateString = $activity->updatedAt;
                $dateTime = new DateTime($dateString);

                $formattedDate = $dateTime->format('d/m/Y');

                $formattedTime = $dateTime->format('h:i A');

          
                return [
                    'id' => $activity->id,
                    'location' => $activity->location,
                    'deviceType' => $activity->deviceType,
                    'ipAddress' => $activity->ipAddress,
                    'ipAddressOwner' => $activity->IpAddressOwner,
                    'status' => $activity->status,
                    'lastAccess' => $formattedDate." ". $formattedTime
                ];
            });

            $yourUserActivity = UserLogActivity::where("userId", $userId)->where('deviceId', $deviceId)->get();
            $yourOwnSession = $yourUserActivity->map(function ($activity) {

                $dateString = $activity->updatedAt;
                $dateTime = new DateTime($dateString);

                $formattedDate = $dateTime->format('d/m/Y');

                $formattedTime = $dateTime->format('h:i A');
                return [
                    'id' => $activity->id,
                    'location' => $activity->location,
                    'deviceType' => $activity->deviceType,
                    'ipAddress' => $activity->ipAddress,
                    'ipAddressOwner' => $activity->IpAddressOwner,
                    'status' => $activity->status,
                    'lastAccess' => $formattedDate." ". $formattedTime

                ];
            });

            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(6)->format('Y-m-d');

            $timeSpendData = DB::table('time_spend_durations')
                ->select('date', DB::raw('SUM("timeSpend") as total_time_spend'))
                ->where('userId', $userId)
                ->whereBetween('date', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $averageTimeSpend = round($timeSpendData->avg('total_time_spend'));

            $weekData = [];

            $daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

            $today = now()->format('l');
            $currentDayIndex = array_search($today, $daysOfWeek);

            for ($i = 1; $i <= 7; $i++) {
                $dayIndex = ($currentDayIndex + $i) % 7;
                $currentDayName = $daysOfWeek[$dayIndex];

                if ($i < 7 && $currentDayName === $today && !$dataForToday) {
                    continue;
                }

                $dataForDay = $timeSpendData->first(function ($item) use ($currentDayName) {
                    return is_string($item->date) ? date('l', strtotime($item->date)) === $currentDayName : $item->date->format('l') === $currentDayName;
                });

                if ($dataForDay) {
                    $timeSpend = $this->formatTime($dataForDay->total_time_spend);
                    $timeSpendForValue = $dataForDay->total_time_spend;
                } else {
                    $timeSpend = $this->formatTime(0);
                    $timeSpendForValue = 0;
                }

                $weekData[] = [
                    'day' => $currentDayName,
                    'time_spend' => $timeSpend,
                    'isToday' => $currentDayName === $today,
                    'value' => $timeSpendForValue
                ];

                if ($currentDayName === $today) {
                    $dataForToday = $dataForDay;
                }
            }

            if ($dataForToday && !$weekData[count($weekData) - 1]['isToday']) {
                $timeSpend = $this->formatTime($dataForToday->total_time_spend);
                $timeSpendForValue = $dataForToday->total_time_spend;
                $weekData[] = [
                    'day' => $today,
                    'time_spend' => $timeSpend,
                    'isToday' => true,
                    'value' => $timeSpendForValue
                ];
            }



            $averageTimeSpend = $this->formatTime($averageTimeSpend);

            $results = [
                'average_time_spend' => $averageTimeSpend,
                'week_data' => $weekData,
            ];

            return response()->json(['status' => 'success', 'message' => "Current Log Activity", "timeSpend" => $results, "otherSession" => $mappedUserActivity, 'currentSession' => $yourOwnSession], 200);

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
    public function formatTime($minutes)
    {
        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        } else {
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
    }
    /**
     * @OA\Get(
     *     path="/api/activityOnPost",
     *     summary="Fetch all Post with your activity",
     *     tags={"User Activity"},
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
    public function activityOnPost(Request $request)
    {
        $partyId = $request->partyId;
        $postType = $request->postType;
        $currentPage = request('page', 1);

        $activity = $partyId != '' ? $partyId : Auth::user()->id;
        $type = request('postType', 'Complaints');
        $activity = $activity . '|' . $type;
        // $allPost = FetchAllPost::getAllPost($currentPage,$partyId,null,$activity,null,null,null);
        $allPost = OptimizeFetchPost::getAllPost($currentPage, $partyId, null, $activity, null, null, null);

        return response()->json(['status' => 'success', 'message' => "All Post Activity Result", "result" => $allPost], 200);

    }
}
