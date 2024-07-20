<?php

namespace App\Http\Controllers\Task;

use App\Helpers\LogActivity;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TaskManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     *     path="/api/task",
     *     summary="Fetch all task",
     *     tags={"Task"},
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
            $assignBy = $partyId ? $partyId : Auth::user()->id;
            $tasks = Task::where('assignBy', $assignBy)->where('taskTitle','like',"%$keyword%")->with('user')->orderBy('createdAt','desc')->get();
            $tasksArray = $tasks->map(function ($task) {
                $name = $task->user->firstName . " " . $task->user->lastName;
                $formattedDate = \Carbon\Carbon::parse($task->createdAt)->format('d/m/Y');
                return [
                    'id' => $task->id,
                    'assignTo' => $name,
                    'assignToPhoneNumber' => $task->user->phoneNumber,
                    'taskTitle' => $task->taskTitle,
                    'taskDescription' => $task->taskDescription,
                    'startDate' => $task->startDate,
                    'endDate' => $task->endDate,
                    'startTime' => $task->startTime,
                    'endTime' => $task->endTime,
                    'status' => $task->status,
                    'createdAt' => $formattedDate,
                ];
            });
            $desiredTotal = $tasksArray->count();
            $pagedPosts = $tasksArray->forPage($currentPage, $perPage)->values();
            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return response()->json(['status' => 'success', 'message' => 'Task List', 'result' => $list], 200);
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
     *     path="/api/task",
     *     summary="Add new task",
     *     tags={"Task"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"assignTo", "taskTitle", "taskType" ,"taskDescription" ,"subTask", "startDate" ,"endDate"},
     *         @OA\Property(property="assignTo", type="string", example="Kiran"),
     *         @OA\Property(property="taskTitle", type="string", example="taskTitle"),
     *         @OA\Property(property="taskType", type="string", example="taskType"),
     *         @OA\Property(property="taskDescription", type="string", example="taskDescription"),
     *         @OA\Property(property="subTask", type="string", example="subTask"),
     *         @OA\Property(property="startDate", type="string", example="startDate"),
     *         @OA\Property(property="endDate", type="string", example="endDate"),
     *         @OA\Property(property="startTime", type="string", example="startTime"),
     *         @OA\Property(property="endTime", type="string", example="endTime"),
     *         @OA\Property(property="userType", type="string", example="employee/volunteer/leader"),
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
            $assignTo = $request->assignTo;
            $assignByType = $partyId ? "Party" : Auth::user()->getRoleNames()[0];
            $assignBy = $partyId ? $partyId : Auth::user()->id;
            $taskType = $request->taskType;
            $taskTitle = $request->taskTitle;
            $taskDescription = $request->taskDescription;
            $subTask = $request->subTask;
            $startDate = $request->startDate;
            $endDate = $request->endDate;
            $startTime = $request->startTime;
            $endTime = $request->endTime;
            $userType = $request->userType;

            $overlappingTask = Task::where('assignTo', $assignTo)
            ->where('startDate', '<=', $endDate)
            ->where('endDate', '>=', $startDate)
            ->where('startTime', '<=', $endTime)
            ->where('endTime', '>=', $startTime)
            ->exists();

        if ($overlappingTask) {
            return response()->json(['status' => 'error', 'message' => 'Another task is already assigned to this user at the given time.'], 400);
        }
            $data = [
                'assignTo' => $assignTo,
                'assignBy' => $assignBy,
                'assignByType' => $assignByType,
                'taskType' => $taskType,
                'taskTitle' => $taskTitle,
               'taskDescription' => $taskDescription,
                'subTask' => $subTask,
                'status' => 'Active',
                'startDate' => $startDate,
                'endDate' => $endDate,
                'startTime' => $startTime,
                'endTime' => $endTime,
                'userType' => $userType,
                'createdBy' => Auth::user()->id,
                'updatedBy' => Auth::user()->id,
            ];
            Task::create($data);
            return response()->json(['status' => 'success', 'message' => 'Task Created Successfully'], 200);
        } catch (\Exception $e) {
            return($e->getMessage());
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * Display the specified resource.
     */

    
      /**
     * @OA\Get(
     *     path="/api/task/{id}",
     *     summary="Fetch task by id",
     *     tags={"Task"},
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
          $task = Task::where('id', $id)->with('user')->first();
            $name = $task->user->firstName . " " . $task->user->lastName;
            $formattedDate = \Carbon\Carbon::parse($task->createdAt)->format('d/m/Y');
           $task = [
            'id' => $task->id,
            'assignTo' => $task->assignTo,
            'assignToName' => $name,
            'assignToPhoneNumber' => $task->user->phoneNumber,
            'taskTitle' => $task->taskTitle,
            'taskDescription' => $task->taskDescription,
            'taskType' => $task->taskType,
            'startDate' => $task->startDate,
            'endDate' => $task->endDate,
            'startTime' => $task->startTime,
            'endTime' => $task->endTime,
            'status' => $task->status,
            'createdAt' => $formattedDate,

           ]; 
           return response()->json(['status' => 'success', 'message' => 'Task list' ,'result' => $task ], 200);

    
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
     *   path="/api/task/{id}",
     *     summary="Update task by id",
     *     tags={"Task"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"taskTitle","taskDescription","status"},
     *         @OA\Property(property="taskTitle", type="string", example="Sr Manager"),
     *         @OA\Property(property="taskDescription", type="string", example="Manager"),
     *        @OA\Property(property="status", type="string", example="Manager"),      
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
    public function update(Request $request, $id)
    {
        try {
            $task = Task::find($id);
    
            if (!$task) {
                return response()->json(['status' => 'error', 'message' => 'Task not found'], 404);
            }
    
            $task->taskTitle = !empty($request->taskTitle) ? $request->taskTitle : $task->taskTitle;
            $task->taskDescription = !empty($request->taskDescription) ? $request->taskDescription : $task->taskDescriptions;
            $task->assignByType = !empty($request->assignByType) ? $request->assignByType : $task->assignByType;
            $task->status = !empty($request->status) ? $request->status : $task->status;
            $task->updatedBy = Auth::user()->id;
    
            $task->save();
    
            return response()->json(['status' => 'success', 'message' => 'Task updated successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    /**
     * @OA\Get(
     *     path="/api/getTaskType",
     *     summary="Fetch all getTaskType",
     *     tags={"Task"},
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
    public function getTaskType()
    {
        try {
            $taskType = TaskType::all();
            return response()->json(['status' => 'success', 'result' => $taskType], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/taskAssignedTo",
     *     summary="Fetch all taskAssignedTo",
     *     tags={"Task"},
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
 public function taskAssignedTo()
{
    try {
        $partyId = request('partyId');
        $assignBy = $partyId ? $partyId : Auth::user()->id;
       
        $citizenArray = DB::table(DB::raw('(
                select distinct "volunteers"."volunteersTo" as "id"
                from "volunteers"
                where "volunteers"."status" = \'Active\' 
                and "volunteers"."volunteersCreatedTypeId" = \'' . $assignBy . '\'
                union 
                select "employees"."employeeToId" as "id"
                from "employees"
                where "employees"."status" = \'Active\' 
                and "employees"."employeeCreatedBy" = \'' . $assignBy . '\'
            ) as temp'))
            ->select('temp.id', DB::raw('CONCAT(volunteer_user."firstName", \' \', volunteer_user."lastName") as name'), DB::raw("'volunteers' as userType"))
            ->leftJoin('users as volunteer_user', 'temp.id', '=', 'volunteer_user.id')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json(['status' => 'success', 'result' => $citizenArray], 200);
    } catch (\Exception $e) {
        LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
    }
}


}
