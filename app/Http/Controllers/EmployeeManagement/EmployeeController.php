<?php

namespace App\Http\Controllers\EmployeeManagement;

use Auth;
use App\Models\User;
use App\Models\Party;
use App\Models\Expense;
use App\Models\Employee;
use App\Helpers\LogActivity;
use Illuminate\Http\Request;
use App\Helpers\EncryptionHelper;
use App\Models\EmployeeeDepartment;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/getdepartMentList",
     *     summary="Fetch all getdepartMentList",
     *     tags={"Employee"},
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
    public function getdepartMentList()
    {
        try {
            $data = EmployeeeDepartment::select('id', 'departmentName')->get();
            return response()->json(['status' => 'success', 'message' => 'Department Lists', 'result' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }
    /**
     * @OA\Get(
     *     path="/api/employee",
     *     summary="Fetch all Employees",
     *     tags={"Employee"},
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
            $employeeCreatedBy = $partyId ? $partyId : Auth::user()->id;
            $employee = Employee::where('employeeCreatedBy', $employeeCreatedBy)
                ->whereHas('user', function ($query) use ($keyword) {
                    $terms = explode(' ', $keyword);

                    $query->where(function ($subquery) use ($terms) {
                        foreach ($terms as $term) {
                            $subquery->orWhereRaw('LOWER("firstName") LIKE ? OR LOWER("lastName") LIKE ?', [
                                "%" . strtolower($term) . "%",
                                "%" . strtolower($term) . "%",
                            ]);
                        }
                    });
                })
                ->with('user')
                ->get();

            $employeeArray = $employee->map(function ($employees) {
                $name = $employees->user->firstName . " " . $employees->user->lastName;
                $formattedDate = \Carbon\Carbon::parse($employees->createdAt)->format('d/m/Y');
                return [
                    'id' => $employees->id,
                    'empCode' => $employees->empcode,
                    'employeer' => $name,
                    'employeerPhoneNumber' => $employees->user->phoneNumber,
                    'jobRole' => $employees->jobRole,
                    'status' => $employees->status,
                    'workExperince' => $employees->workExperince,
                    'createdAt' => $formattedDate,
                ];
            });
            $desiredTotal = $employeeArray->count();
            $pagedPosts = $employeeArray->forPage($currentPage, $perPage)->values();

            $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
            return response()->json(['status' => 'success', 'message' => 'EmployeeList', 'result' => $list], 200);
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
     * @OA\Post(
     *     path="/api/employee",
     *     summary="Add Employee",
     *     tags={"Employee"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Employee",
     *     @OA\JsonContent(
     *         required={"partyId","employeeTo","employeeDepartmentId","jobRole", "maxEducation", "annualCTC","dateOfJoining", "referenceName","reportingManager","comments","status"},
     *         @OA\Property(property="partyId", type="string", example="partyId"),
     *        @OA\Property(property="employeeDepartmentId", type="string", example="dfdf3"),
     *        @OA\Property(property="jobRole", type="string", example="string"),
     *       @OA\Property(property="employeeTo", type="string", example="string"),
     *        @OA\Property(property="annualCTC", type="string", example="string"),
     *        @OA\Property(property="reportingManager", type="string", example="reportingManager"),
     *        @OA\Property(property="maxEducation", type="string", example="string"),
     *        @OA\Property(property="dateOfJoining", type="string", example="string"),
     *        @OA\Property(property="referenceName", type="string", example="string"),
     *        @OA\Property(property="referencePhone", type="string", example="string"),
     *        @OA\Property(property="comments", type="string", example="string"),
     *        @OA\Property(property="status", type="string", example="string"),
     *        @OA\Property(property="workExperince", type="string", example="workExperince"),
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
        $partyId = $request->partyId;
        $roleName = $partyId ? "Party" : Auth::user()->getRoleNames()[0];
        $userId = $partyId ? $partyId : Auth::user()->id;
        $employeeTo = $request->employeeTo;
        $maxEducation = $request->input('maxEducation');
        Employee::create([
            'id' => \DB::raw('gen_random_uuid()'),
            'partyId' => $partyId,
            'employeeToId' => $employeeTo,
            'employeeCreatedType' => $roleName,
            'employeeCreatedBy' => $userId,
            'employeeDepartmentId' => $request->input('employeeDepartmentId'),
            'dateOfJoining' => $request->input('dateOfJoining'),
            'referenceName' => $request->input('referenceName'),
            'referencePhone' => $request->input('referencePhone'),
            'jobRole' => $request->input('jobRole'),
            'comments' => $request->input('comments'),
            'status' => $request->input('status'),
            'reportingManager' => $request->input('reportingManager'),
            'annualCTC' => $request->input('annualCTC'),
            'workExperince' => $request->input('workExperince'),
            'maxEducation' => $maxEducation,
            'createdBy' => Auth::user()->id,
            'updatedBy' => Auth::user()->id,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Employee Saved Succesfully'], 200);

    }


    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/employeeList",
     *     summary="Fetch all UserLists",
     *     tags={"Employee"},
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
    public function employeeList()
    {
        try {
            $userId = Auth::user()->id;
            $data = User::role('Citizen')
                ->whereNotIn('id', function ($query) use ($userId) {
                    $query->select('employeeToId')
                        ->from('employees')
                        ->where('employeeToId', $userId);
                })
                ->get();
            return response()->json(['status' => 'success', 'message' => 'Employee Lists', 'result' => $data], 200);
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
     *     path="/api/employee/{id}",
     *     summary="Fetch Employee by id",
     *     tags={"Employee"},
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
            $partyId = request('partyId');
            $employeeCreatedBy = $partyId ? $partyId : Auth::user()->id;
            $employee = Employee::where('id', $id)->with('user')->get();
            $employeeArray = $employee->map(function ($employees) {
                $name = $employees->user->firstName . " " . $employees->user->lastName;
                $formattedDate = \Carbon\Carbon::parse($employees->createdAt)->format('d/m/Y');
                $department = EmployeeeDepartment::where('id', $employees->employeeDepartmentId)->first();
                return [
                    'id' => $employees->id,
                    'empCode' => $employees->empcode,
                    'employeeToId' => $employees->employeeToId,
                    'employeeToName' => $name,
                    'employeeDepartmentId' => $employees->employeeDepartmentId,
                    'employeeDepartmentName' => $department->departmentName,
                    'employeerPhoneNumber' => $employees->user->phoneNumber,
                    'jobRole' => $employees->jobRole,
                    'status' => $employees->status,
                    'dateOfJoining' => $employees->dateOfJoining,
                    'workExperince' => $employees->workExperince,
                    'referenceName' => $employees->referenceName,
                    'referencePhone' => $employees->referencePhone,
                    'comments' => $employees->comments,
                    'reportingManager' => $employees->reportingManager,
                    'annualCTC' => $employees->annualCTC,
                    'maxEducation' => $employees->maxEducation,
                    'createdAt' => $formattedDate,
                ];
            });

            return response()->json(['status' => 'success', 'message' => 'EmployeeList', 'result' => $employeeArray], 200);
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
     */

    /**
     *   @OA\Put(
     *   path="/api/employee/{id}",
     *     summary="Update Employee by id",
     *     tags={"Employee"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"status"},
     *         @OA\Property(property="status", type="string", example="Sr Manager"),
     *         
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
    public function update(Request $request, string $id)
    {
        try {
            $employee = Employee::find($id);

            if (!$employee) {
                return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
            }
            $status = $request->status ?? $employee->status;
            $employeeTo = $request->employeeToId ?? $employee->employeeToId;
            $maxEducation = $request->maxEducation ?? $employee->maxEducation;
            $workExperince = $request->workExperince ?? $employee->workExperince;
            $employeeCreatedType = $request->employeeCreatedType ?? $employee->employeeCreatedType;
            $employeeCreatedBy = $request->employeeCreatedBy ?? $employee->employeeCreatedBy;
            $employeeDepartmentId = $request->employeeDepartmentId ?? $employee->employeeDepartmentId;
            $dateOfJoining = $request->dateOfJoining ?? $employee->dateOfJoining;
            $referenceName = $request->referenceName ?? $employee->referenceName;
            $jobRole = $request->jobRole ?? $employee->jobRole;
            $comments = $request->comments ?? $employee->comments;
            $reportingManager = $request->reportingManager ?? $employee->reportingManager;
            $annualCTC = $request->annualCTC ?? $employee->annualCTC;

            $employee->status = $status;
            $employee->employeeToId = $employeeTo;
            $employee->maxEducation = $maxEducation;
            $employee->employeeCreatedType = $employeeCreatedType;
            $employee->employeeCreatedBy = $employeeCreatedBy;
            $employee->employeeDepartmentId = $employeeDepartmentId;
            $employee->dateOfJoining = $dateOfJoining;
            $employee->referenceName = $referenceName;
            $employee->jobRole = $jobRole;
            $employee->comments = $comments;
            $employee->reportingManager = $reportingManager;
            $employee->annualCTC = $annualCTC;
            $employee->workExperince = $workExperince;
            $employee->save();

            return response()->json(['status' => 'success', 'message' => 'Employee updated successfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => 'Server Error'], 500);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/employee/{id}",
     *     summary="Delete Employee id",
     *     tags={"Employee"},
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
    public function destroy(string $id)
    {
        $employee = Employee::find($id);
        if (!$employee) {
            return response()->json(['status' => 'error', 'message' => 'Employee not found'], 404);
        }
        $employeeCreatedBy = $employee->employeeCreatedBy;
        $employeeCreatedType = $employee->employeeCreatedType;
        $employeeToId = $employee->employeeToId;
        $expense = Expense::where('expenseBy',$employeeToId)
        ->where('expenseCreatedByType',$employeeCreatedType)
        ->where('expenseCreatedBy',$employeeCreatedBy)
        ->delete();
        $employee->delete();
        return response()->json(['status' => 'success', 'message' => 'Employee deleted successfully'], 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Post(
     *     path="/api/searchEmployeeWithAadhar",
     *     summary="Fetch all search Employee With Aadhar",
     *     tags={"Employee"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Search keyword for employee",
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="keyword",
     *                     type="string",
     *                     example="John",
     *                     description="The search keyword for employee"
     *                 ),
     *             )
     *         )
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


    public function searchEmployeeWithAadhar(Request $request)
    {
        try {
            $keyword = $request->keyword;
            $userId = Auth::user()->id;

            if ($keyword != '') {
                $aadhar = EncryptionHelper::encryptString($keyword);
                $user = User::role('Citizen')
                    ->where('aadharNumber', $aadhar)->first();

                if (!$user) {
                    return response()->json(['status' => 'error', 'message' => 'No registered Citizens found with the given Aadhar number '], 404);
                }
                if (Employee::where('employeeToId', $user->id)->exists()) {
                    return response()->json(['status' => 'error', 'message' => 'Already employeed by some other leader or party'], 404);
                }
                $fullName = $user->firstName . ' ' . $user->lastName;
                $userId = $user->id;

                $userDetails = [
                    'userId' => $userId,
                    'name' => $fullName,
                ];

                return response()->json(['status' => 'success', 'message' => 'Citizen Details', 'result' => $userDetails], 200);
            }
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/reportingManager",
     *     summary="Fetch all reporting Manager",
     *     tags={"Employee"},
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

    public function reportingManager()
    {
        try {
            $partyId = request('partyId');
            $leaderorPartyId = $partyId ? $partyId : Auth::user()->id;
            
            if ($partyId != '') {
                $party = Party::find($leaderorPartyId);
                $userId = $party->id;
                $name = $party->name;
            } else {
                $user = User::find($leaderorPartyId);
                $userId = $user->id;
                $name = $user->firstName . " " . $user->lastName;
            }
        
            $ownUser = [
                "userId" => $userId,
                "name" => $name,
            ];
        
            $employee = Employee::where('employeeCreatedBy', $leaderorPartyId)->get();
        
            $processedData = $employee->map(function ($employee) {
                $userId = $employee->employeeToId;
                $user = User::find($userId);
                $firstName = $user->firstName;
                $lastName = $user->lastName;
        
                return [
                    'userId' => $user->id,
                    'name' => $firstName . ' ' . $lastName,
                ];
            });
        
            $mergedData =  array_merge([$ownUser], $processedData->toArray());
        
            return response()->json(['status' => 'success', 'message' => 'Reporting Manager Details', 'result' => $mergedData], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
        }
        
        
    }

}