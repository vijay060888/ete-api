<?php

namespace App\Http\Controllers\Expense;

use App\Http\Controllers\Controller;
use App\Models\Party;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use App\Helpers\LogActivity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    /**
     * @OA\Get(
     *     path="/api/expenses",
     *     summary="Fetch all Expense",
     *     tags={"Expense"},
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
    public function index(Request $request)
    {
        //$partyId = $request->has('id') ? $request->query('id') : Auth::user()->id;
        $partyId = $request->input('partyId');
        $searchKeyword = $request->input('keyword');
        $partyId = $partyId ? $partyId : Auth::user()->id;
        if ($searchKeyword != '') {
            try {
                $perPage = env('PAGINATION_PER_PAGE', 10);
                $partyId = $request->input('partyId', null);
                $partyId = $partyId ? $partyId : Auth::user()->id;
                $checkIfExists = Expense::where('expenseCreatedBy', $partyId)
                    ->where(function ($query) use ($searchKeyword) {
                        $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                            ->orWhereRaw('LOWER("hashTag") LIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                            ->orWhereRaw('LOWER("expenseTowards") LIKE ?', ['%' . strtolower($searchKeyword) . '%']);
                    })
                    ->exists();
                $data = [
                    "totalExpense" => $this->YearExpense($partyId, $searchKeyword),
                    "currentMonth" => now()->format('F Y'),
                    "currentExpense" => $this->CurrentMonthExpense($partyId, $searchKeyword),
                    "monthlyExpense" => $this->MonthlyExpenses($partyId, $searchKeyword),
                    "todayExpense" => $this->TodayExpenses($partyId, $searchKeyword),
                    "allExpenses" => $this->AllExpenses($partyId, $searchKeyword),
                ];
                return response()->json(['status' => 'success', 'message' => 'List of Expense', 'result' => $data], 200);

            } catch (\Exception $e) {
                LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
                return response()->json(['status' => 'error', 'message' => 'server error'], 404);
            }
        }

        try {
            // $checkIfPartyIdExists = Expense::where('expenseCreatedBy', $partyId)->exists();
            $checkIfPartyIdExists = DB::table('expenses')
                ->whereIn('expenseBy', function ($query) use($partyId) {
                    $query->select('employeeToId')
                        ->from('employees')
                        ->where('employeeCreatedBy', '=', $partyId);
                })
                ->orderBy('createdAt', 'desc')
                ->get();
            if (!$checkIfPartyIdExists) {
                return response()->json(['status' => 'failed', 'message' => 'Expense not Found'], 200);
            }
            $data = [
                "totalExpense" => $this->YearExpense($partyId, $searchKeyword),
                "currentMonth" => now()->format('F Y'),
                "currentExpense" => $this->CurrentMonthExpense($partyId, $searchKeyword),
                "monthlyExpense" => $this->MonthlyExpenses($partyId, $searchKeyword),
                "todayExpense" => $this->TodayExpenses($partyId, $searchKeyword),
                // "allExpenses" => $checkIfPartyIdExists
                "allExpenses" => $this->AllExpenses($partyId, $searchKeyword),
            ];
            return response()->json(['status' => 'success', 'message' => 'List of Expense', 'result' => $data], 200);
        
        } catch (\Exception $e) {
            return $e->getMessage();
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    public function YearExpense($partyID, $searchKeyword)
    {
        $currentYear = date('Y');
        $totalExpense = DB::table('expenses')
            ->where('expenseCreatedBy', $partyID)
            ->whereIn('expenseBy', function ($query) use($partyID) {
                $query->select('employeeToId')
                    ->from('employees')
                    ->where('employeeCreatedBy', '=', $partyID);
            })
            ->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                    ->orWhereRaw('LOWER("hashTag") ILIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER("expenseTowards") ILIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->whereYear('createdAt', $currentYear)
            ->sum('totalExpense');
        return $totalExpense;
    }

    public function TodayExpenses($partyID, $searchKeyword)
    {
        $currentDate = now()->toDateString();
        $expenses = DB::table('expenses')
            ->where('expenseCreatedBy', $partyID)
            ->whereIn('expenseBy', function ($query) use($partyID) {
                $query->select('employeeToId')
                    ->from('employees')
                    ->where('employeeCreatedBy', '=', $partyID);
            })
            ->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                    ->orWhereRaw('LOWER("hashTag") ILIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER("expenseTowards") ILIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->whereDate('createdAt', $currentDate)
            ->get();

        $expenseArray = $expenses->map(function ($expense) {
            $expenseUser = Expense::find($expense->id)->user;

            if ($expenseUser === null) {
                $expenseUser = Expense::find($expense->id)->party;
            }
            $expenseTowards = $expense->expenseTowards;
            $expenseAmount = $expense->totalExpense;
            $expenseUserName = '';
            if ($expenseUser !== null) {
                if (isset($expenseUser['firstName']) && isset($expenseUser['lastName'])) {
                    $expenseUserName = $expenseUser->firstName . " " . $expenseUser->lastName;
                } elseif (isset($expenseUser['name'])) {
                    $expenseUserName = $expenseUser->name;
                }
            }
           $user = User::where('id', $expense->expenseBy)->first();
            $userName = ($user !== null) ? $user->userName : '';
            return [
                'expenseId' => $expense->id,
                'expenseTowards' => $expenseTowards,
                'expenseAmount' => $expenseAmount,
                'expenseUserName' => $userName,
                'createdAt' => \Carbon\Carbon::parse($expense->createdAt)->format('d/m/Y')
            ];

        });
        return $expenseArray;
    }

    public function CurrentMonthExpense($partyID, $searchKeyword)
    {
        $expense = DB::table('expenses')
            ->where('expenseCreatedBy', $partyID)
            ->whereYear('createdAt', now()->year)
            ->whereIn('expenseBy', function ($query) use($partyID) {
                $query->select('employeeToId')
                    ->from('employees')
                    ->where('employeeCreatedBy', '=', $partyID);
            })
            ->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                    ->orWhereRaw('LOWER("hashTag") ILIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER("expenseTowards") ILIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->whereMonth('createdAt', now()->month)
            ->sum('totalExpense');
        return $expense;
    }

    public function AllExpenses($partyId, $searchKeyword)
    {
        $currentPage = request('page', 1);
        $perPage = env('PAGINATION_PER_PAGE', 10);
        $currentYear = date('Y');
        $expenses = Expense::where('expenseCreatedBy', $partyId)
            ->whereIn('expenseBy', function ($query) use($partyId) {
                $query->select('employeeToId')
                    ->from('employees')
                    ->where('employeeCreatedBy', '=', $partyId);
            })
            ->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                    ->orWhereRaw('LOWER("hashTag") ILIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER("expenseTowards") ILIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->paginate($perPage, ['*'], 'page', $currentPage);
        
        $expenseArray = $expenses->map(function ($expense) {
            $expenseUser = Expense::find($expense->id)->user;

            if ($expenseUser === null) {
                $expenseUser = Expense::find($expense->id)->party;
            }
            $expenseTowards = $expense->expenseTowards;
            $expenseAmount = $expense->totalExpense;
            $expenseUserName = '';
            if ($expenseUser !== null) {
                if (isset($expenseUser['firstName']) && isset($expenseUser['lastName'])) {
                    $expenseUserName = $expenseUser->firstName . " " . $expenseUser->lastName;
                } elseif (isset($expenseUser['name'])) {
                    $expenseUserName = $expenseUser->name;
                }
            }

            $createdAt = $expense->createdAt;
            $user = User::where('id', $expense->expenseBy)->first();
            $userName = ($user !== null) ? $user->userName : '';
            return [
                'expenseId' => $expense->id,
                'expenseTowards' => $expenseTowards,
                'expenseAmount' => $expenseAmount,
                'expenseUserName' => $userName,
                'createdAt' => \Carbon\Carbon::parse($createdAt)->format('d/m/Y')
            ];
        });
        /*** paginator code */
        $desiredTotal = $expenseArray->count();
        $pagedPosts = $expenseArray->forPage($currentPage, $perPage)->values();
        $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $currentPage, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
        /*** paginator code */
        return $list;
    }

    public function MonthlyExpenses($partyID, $searchKeyword)
    {
        $monthsOfYear = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $currentMonthName = now()->format('F');
        $expenses = [];

        for ($i = 0; $i < 12; $i++) {
            $monthName = $monthsOfYear[$i];
            $isActive = ($currentMonthName === $monthName);

            // Sum of expenses in this month
            $expense = DB::table('expenses')
                ->where('expenseCreatedBy', $partyID)
                ->whereYear('createdAt', now()->year)
                ->whereMonth('createdAt', $i + 1)
                ->whereIn('expenseBy', function ($query) use($partyID) {
                    $query->select('employeeToId')
                        ->from('employees')
                        ->where('employeeCreatedBy', '=', $partyID);
                })
                ->where(function ($query) use ($searchKeyword) {
                    $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                        ->orWhereRaw('LOWER("hashTag") ILIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                        ->orWhereRaw('LOWER("expenseTowards") ILIKE ?', ['%' . strtolower($searchKeyword) . '%']);
                })
                
                ->sum('totalExpense');


            $expenses[] = [
                'month' => $monthName,
                'currentMonth' => $isActive,
                'expense' => $expense
            ];
        }
        return $expenses;
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
     *     path="/api/expenses",
     *     summary="Add Expense",
     *     tags={"Expense"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Expense",
     *     @OA\JsonContent(
     *         required={"totalExpense","expenseBy","expenseTowards","paymentMode","nameOfVendor","Description","expenseDate","transaction","hashTag","invoice","expenseCreatedBy","expenseCreatedByType"},
     *        @OA\Property(property="totalExpense", type="string", example="2000"),    
     *        @OA\Property(property="partyId", type="string", example="9a85e01e-f737-417b-b451-ff753ea6cf07"),   
     *        @OA\Property(property="expenseBy", type="string", example="9a85e01e-f737-417b-b451-ff753ea6cf07"),
     *        @OA\Property(property="expenseTowards", type="string", example="Development"),
     *        @OA\Property(property="paymentMode", type="string", example="UPI"),
     *        @OA\Property(property="nameOfVendor", type="string", example="ABc"),
     *        @OA\Property(property="Description", type="string", example="testing"),
     *        @OA\Property(property="expenseDate", type="0/1", example="2023-01-01"),
     *        @OA\Property(property="transaction", type="string", example="transaction"),
     *        @OA\Property(property="hashTag", type="string", example="#hashTag"),
     *        @OA\Property(property="invoice", type="string", example="images/picture.jpg"),
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
        $expenseCreatedByType = $partyId ? "Party" : "Leader";
        $expenseCreatedBy = $partyId ? $partyId : Auth::user()->id;
        $totalExpense = $request->input('totalExpense');
        $expenseBy = $request->input('expenseBy');
        $expenseTowards = $request->input('expenseTowards');
        $paymentMode = $request->input('paymentMode');
        $nameOfVendor = $request->input('nameOfVendor');
        $Description = $request->input('Description');
        $expenseDate = $request->input('expenseDate');
        $hashTag = $request->input('hashTag');
        $invoice = $request->input('invoice');
        $transaction = $request->input('transaction');
        $userId = Auth::user()->id;

        Expense::create([
            'id' => \DB::raw('gen_random_uuid()'),
            'totalExpense' => $totalExpense,
            'expenseBy' => $expenseBy,
            'expenseTowards' => $expenseTowards,
            'paymentMode' => $paymentMode,
            'nameOfVendor' => $nameOfVendor,
            'Description' => $Description,
            'expenseDate' => $expenseDate,
            'transaction' => $transaction,
            'hashTag' => $hashTag,
            'invoice' => $request->input('invoice'),
            'authorize' => false,
            'transaction' => $transaction,
            'expenseCreatedByType' => $expenseCreatedByType,
            'expenseCreatedBy' => $expenseCreatedBy,
            'createdBy' => $userId,
            'updatedBy' => $userId,
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
        return response()->json(['status' => 'success', 'message' => 'Expense Saved Succesfully'], 200);
    }

    /**
     * Display the specified resource.
     */
    /**
     * @OA\Get(
     *     path="/api/expenses/{id}",
     *     summary="Fetch Expense by id",
     *     tags={"Expense"},
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
    public function show($id)
    {
        try {
            $expense = Expense::find($id);
            // return $expense;
            if (!$expense) {
                return response()->json(['status' => 'success', 'message' => 'Expense not Found'], 200);
            }
            $employee = Employee::where("employeeToId", $expense->expenseBy)->first();
           if (!$employee) {
                return response()->json(['status' => 'success', 'message' => 'Employee not Found'], 200);
            }
            
            $user_id = $expense->expenseBy;

            $expenseuser_name = User::where('id', $user_id)->first();
            if( $expenseuser_name=='')
            {
             $party =   Party::find($user_id);
             $userName =   $party->nameAbbrevation;
            }
            else{
                $userName =    $expenseuser_name->getFullName();
            }
            $data = [
                'id' => $expense->id,
                'totalExpense' => $expense->totalExpense,
                'expenseBy' =>    $expenseuser_name->id,
                'expenseTowards' => $expense->expenseTowards,
                'paymentMode' => $expense->paymentMode,
                'nameOfVendor' => $expense->nameOfVendor,
                'Description' => $expense->Description,
                'expenseDate' => $expense->expenseDate,
                'transaction' => $expense->transaction,
                'hashTag' => $expense->hashTag,
                'invoice' => $expense->invoice,
                'expenseCreatedByType' => $expense->expenseCreatedByType,
                'expenseCreatedBy' => $expense->expenseCreatedBy,
                'remarks' => $expense->remarks,
                'authorize' => $expense->authorize,
            ];

            return response()->json(['status' => 'success', 'message' => 'View Expenses', 'result' => $data], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getTraceAsString()], 404);
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     *   @OA\Put(
     *   path="/api/expenses/{id}",
     *     summary="Update expense by id",
     *     tags={"Expense"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *     ),
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter details",
     *     @OA\JsonContent(
     *         required={"totalExpense","expenseBy","expenseTowards","paymentMode","nameOfVendor","Description","expenseDate",  "transaction","hashTag","invoice"},
     *        @OA\Property(property="totalExpense", type="string", example="2000"),   
     *        @OA\Property(property="expenseBy", type="string", example="9a85e01e-f737-417b-b451-ff753ea6cf07"),
     *        @OA\Property(property="expenseTowards", type="string", example="Development"),
     *        @OA\Property(property="paymentMode", type="string", example="UPI"),
     *        @OA\Property(property="nameOfVendor", type="string", example="ABc"),
     *        @OA\Property(property="Description", type="string", example="testing"),
     *        @OA\Property(property="expenseDate", type="0/1", example="2023-01-01"),
     *        @OA\Property(property="transaction", type="string", example="transaction"),
     *        @OA\Property(property="hashTag", type="string", example="#hashTag"),
     *        @OA\Property(property="invoice", type="string", example="images/picture.jpg"),
     *        @OA\Property(property="authorize", type="Boolean", example=true),   
     *        @OA\Property(property="remarks", type="string", example="remarks"),
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
        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json(['status' => 'success', 'message' => 'Data not Found'], 200);
        }
        try {
            $totalExpense = $request->input('totalExpense');
            $expenseBy = $request->input('expenseBy');
            $expenseTowards = $request->input('expenseTowards');
            $paymentMode = $request->input('paymentMode');
            $nameOfVendor = $request->input('nameOfVendor');
            $Description = $request->input('Description');
            $expenseDate = $request->input('expenseDate');
            $hashTag = $request->input('hashTag');
            $invoice = $request->input('invoice');
            $remarks = $request->input('remarks');
            $authorize = $request->input('authorize');
            $transaction = $request->input('transaction');

            $expense = Expense::find($id);
            $expense->totalExpense = $totalExpense;
            $expense->expenseBy = $expenseBy;
            $expense->expenseTowards = $expenseTowards;
            $expense->paymentMode = $paymentMode;
            $expense->nameOfVendor = $nameOfVendor;
            $expense->Description = $Description;
            $expense->expenseDate = $expenseDate;
            $expense->transaction = $transaction;
            $expense->hashTag = $hashTag;
            $expense->invoice = $invoice;
            $expense->remarks = $remarks;
            $expense->authorize = $authorize;
            $expense->save();
            return response()->json(['status' => 'success', 'message' => 'Expense Updated Succesfully'], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
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
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/expenseUserList",
     *     summary="Fetch all UserLists",
     *     tags={"Expense"},
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
    public function expenseUserList()
    {

        try {
            $partyId = request('partyId');
            $employeeCreatedBy = $partyId ? $partyId : Auth::user()->id;

            $employees = Employee::where('employeeCreatedBy',$employeeCreatedBy)->get();
            $employeeArray = $employees->map(function ($employee) {
                $name = $employee->user->firstName . " " . $employee->user->lastName;
                return [
                    'id' => $employee->employeeToId,
                    'name' => $name,
                ];
            })->unique('name')->values();
            return response()->json(['status' => 'success', 'message' => 'Employee Lists', 'result' => $employeeArray], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }

    }




    /**
     * @OA\Post(
     *     path="/api/generateExpenseReport",
     *     summary="Generate Expense Report",
     *     tags={"Expense"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Enter details",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="partyId",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="partyId", type="string", format="partyId", example="partyId"),
     *                 ),
     *                 example="9a8bf175-1dc1-4860-bb36-60938d2175e6",
     *                 description="Array of leader IDs"
     *             )
     *         )
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
     *         description="Unauthorized",
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *     ),
     *     security={{ "apiAuth": {} }}
     * )
     */
    public function generateExpenseReport(Request $request)
    {
        $partyId = $request->input('partyId', null);
        $searchKeyword = $request->input('keyword');
        $partyId = $partyId ? $partyId : Auth::user()->id;
        // $checkIfPartyIdExists = Expense::where('expenseCreatedBy', $partyId)->exists();
        $checkIfPartyIdExists = DB::table('expenses')
                ->whereIn('expenseBy', function ($query) use($partyId) {
                    $query->select('employeeToId')
                        ->from('employees')
                        ->where('employeeCreatedBy', '=', $partyId);
                })
                ->orderBy('createdAt', 'desc')
                ->get();
        if (!$checkIfPartyIdExists) {
            return response()->json(['status' => 'success', 'message' => 'Data not Found'], 200);
        }
        try {
            $fileName = 'report-' . now()->format('Ymd').mt_rand(100000, 999999) . '.csv';
            $comparision = [
                "totalExpense" => $this->YearExpense($partyId, $searchKeyword ),
                "currentMonth" => now()->format('F Y'),
                "currentExpenses" => $this->CurrentMonthExpense($partyId, $searchKeyword ),
            ];
            $leadersDetails[] = $comparision;
            (new FastExcel($leadersDetails))->export($fileName);
            
            $url = config('app.url') . '/'.$fileName;
            return response()->json(['status' => 'success', 'message' => "File Generated", 'url' => $url], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }

    private function getExpenseDetails($partyId, $searchKeyword, $pageNumber = 1)
    {
        $perPage = env('PAGINATION_PER_PAGE', 10);
        $searchKeyword = request('searchKeyword');

        $getExpenses = Expense::where('expenseCreatedBy', $partyId)
            ->whereIn('expenseBy', function ($query) use($partyId) {
                $query->select('employeeToId')
                    ->from('employees')
                    ->where('employeeCreatedBy', '=', $partyId);
            })
            ->where(function ($query) use ($searchKeyword) {
                $query->whereRaw('DATE("expenseDate") = ?', [date('Y-m-d', strtotime($searchKeyword))])
                    ->orWhereRaw('LOWER("hashTag") LIKE ?', ['%' . strtolower($searchKeyword) . '%'])
                    ->orWhereRaw('LOWER("expenseTowards") LIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->paginate($perPage, ['*'], 'page', $pageNumber);

        $expenseArray = $getExpenses->map(function ($expense) {
            $expenseUser = Expense::find($expense->id)->user;
            $expenseTowards = $expense->expenseTowards;
            $expenseAmount = $expense->totalExpense;
            $expenseUserName = $expenseUser->firstName . " " . $expenseUser->lastName;
            $createdAt = $expense->createdAt;
            return [
                'expenseId' => $expense->id,
                'expenseTowards' => $expenseTowards,
                'expenseAmount' => $expenseAmount,
                'expenseUserName' => $expenseUserName,
                'createdAt' => \Carbon\Carbon::parse($createdAt)->format('d/m/Y')
            ];
        });

        $desiredTotal = $expenseArray->count();
        $pagedPosts = $expenseArray->forPage($pageNumber, $perPage)->values();
        $list = new LengthAwarePaginator($pagedPosts, $desiredTotal, $perPage, $pageNumber, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);

        return $list;
    }
}
