<?php

namespace App\Http\Controllers\Donation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\LogActivity;
use App\Helpers\PaymentGateway;
use App\Models\Leader;
use App\Models\Party;
use App\Models\User;
use App\Models\UserDetails;
use App\Models\Donation;
use App\Models\DonationBankAccountDetail;
use App\Models\PaymentLog;
use Auth;

class DonationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Get(
     *     path="/api/leaderList",
     *     summary="Get Leader List",
     *     tags={"Donation"},
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
    public function leaderList()
    {
        try {
            $leaders = Leader::get();
            $leaderArray = $leaders->map(function ($leader) {
                $name = $leader->user->firstName . " " . $leader->user->lastName;
                $images = $leader->leaderDetails->profileImage;
                return [
                    'id' => $leader->id,
                    'name' => $name,
                    'images' => $images
                ];
            })->unique('name')->values();
            return response()->json(['status' => 'success', 'message' => "Leader Lists", "result" => $leaderArray], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error"], 404);
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Get(
     *     path="/api/search",
     *     summary="Search",
     *     tags={"Donation"},
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
    public function search(Request $request)
    {        
        $searchKeyword = $request->input('searchKeyword', null);

        if ($searchKeyword) {
            try {

            $leaders = Leader::whereHas('user', function ($query) use ($searchKeyword) {
                $query->whereRaw('LOWER("firstName") LIKE ?', ['%' . strtolower($searchKeyword) . '%']);
            })
            ->get();

            $party = Party::whereRaw('LOWER("name") LIKE ?', ['%' . strtolower($searchKeyword) . '%'])
            ->get();

                $leaderArray = $leaders->map(function ($leader) {
                    $name = $leader->user->firstName . " " . $leader->user->lastName;
                    return [
                        'id' => $leader->id,
                        'name' => $name,
                    ];
                })->unique('name')->values();

                $partyArray = $party->map(function ($part) {
                    $name = $part->name;
                    return [
                        'id' => $part->id,
                        'name' => $name,
                    ];
                })->unique('name')->values();

                $result = [
                    'leaders' => $leaderArray,
                    'party' => $partyArray,
                ];
                // return $result;

                return response()->json([
                    'status' => 'success',
                    'message' => "Leader Lists",
                    'result' => $result,
                ], 200);

            } catch (\Exception $e) {
                LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
                return response()->json(['status' => 'error', 'message' => "Server Error"], 500);
            }
        }
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
     /**
     * @OA\Get(
     *     path="/api/partyList",
     *     summary="Get Party List",
     *     tags={"Donation"},
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
    public function partyList()
    {
        try {
            $parties = Party::get();
            $partyArray = $parties->map(function ($party) {
                return [
                    'id' => $party->id,
                    'name' => $party->name,
                    'images' => $party->logo
                ];
            })->values();
            return response()->json(['status' => 'success', 'message' => "Party Lists", "result" => $partyArray], 200);

        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 404);
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

     /**
     * Store a newly created resource in storage.
     */
     /**
     * @OA\Post(
     *     path="/api/donate",
     *     tags={"Donation"},
     *     summary="Make a Donation",     *     
     *     @OA\RequestBody(
     *      required=true,
     *      description="Endpoint for making a Donation.",
     *     @OA\JsonContent(
     *        required={"amount","donatedTo","donatedBy"},
     *        @OA\Property(property="amount", type="integer", example="1000"),
     *        @OA\Property(property="donatedTo", type="integer", example="9a91ea29-8e67-46f9-94db-9829cbe39328"),
     *        @OA\Property(property="donatedBy", type="integer", example="9a85e01e-f737-417b-b451-ff753ea6cf08"),     *        
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
    public function donate(Request $request)
    {
        try {
            $partyId = $request->partyId;
            $donationAmount = $request->input('amount');            
            $donatedBy = Auth::user()->id; 
            $donatedTo = $request->input('donatedTo');
            $status = true;

            $leader = Leader::where('id', $donatedTo)->exists();
            
            $party = Party::where('id', $donatedTo)->exists();

            if ($leader) {
                $donatedToType = 'Leader';
            } elseif ($party) {
                $donatedToType = 'Party';
            } else {
                $donatedToType = 'Unknown';
            }

            $donation_data = Donation::create([
                'amount' => $donationAmount,
                'donatedTo' => $donatedTo,
                'donatedBy' => $donatedBy,
                'donatedToType' => $donatedToType,
            ]);

            $payment_detail = DonationBankAccountDetail::where('userId', $donatedTo)->first();     

                $payment_data = [
                    "orderId" => $donation_data->id,
                    "amount" => $donationAmount,
                ];
               
                $donation = PaymentGateway::getProdUrl($payment_data);

                // return $donation;

            if (!$payment_detail) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Donation Bank Account Details not found for the provided user. Unable to process the donation.',
                ], 404);
            }

            //status should be true after the payment

            // PaymentLog::create([
            //     'donationId' => $donation->id,
            //     'success' => true,
            //     'amount' => $donationAmount,
            //     'accountHolderName' => $payment_detail->accountHolderName,
            //     'ifsc' => $payment_detail->ifsc,
            //     'accountNumber' => $payment_detail->accountNumber,
            // ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Your donation was successful. Thank you for your contribution!',
                // 'donation_id' => $donation->id,
            ], 200);
            
        } catch (\Exception $e) {
            // PaymentLog::create([
            //     'donationId' => isset($donation) ? $donation->id : null,
            //     'success' => false,
            //     'amount' => $donationAmount,
            //     'accountHolderName' => $payment_detail->accountHolderName,
            //     'ifsc' => $payment_detail->ifsc,
            //     'accountNumber' => $payment_detail->accountNumber,
            // ]);

            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json([
                'status' => 'error', 
                'message' => 'Oops! Something went wrong with the donation. Please try again later.',
            ], 404);
        }
        }

        public function paymentDetails(Request $request)
        {
            try {
                //add the payment details
                                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Your donation was successful. Thank you for your contribution!',
                ], 200);
            } catch (\Exception $e) {
                LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
                return response()->json(['status' => 'error','message' => 'Server Error'], 404);
            }
        }

}
