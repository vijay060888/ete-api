<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Helpers\PaymentGateway;
use Illuminate\Http\Request;
use App\Models\SubscriptionTransaction;
use App\Helpers\LogActivity;
use App\Models\Package;

class SubscriptionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/subscription/generatePaymentUrl",
     *     summary="Initiate payment and get payment url",
     *     tags={"Subscription"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Subscription",
     *     @OA\JsonContent(
     *         required={"userId","packageId"},
     *         @OA\Property(property="userId", type="uuid", example="9a8bf175-1dc1-4860-bb36-60938d2175e5"),
     *         @OA\Property(property="packageId", type="uuid", example="9a8bf175-1dc1-4860-bb36-60938d2175e6"),
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
    public function generatePaymentGatewayUrl(Request $request){
        try{
           
           $transaction = SubscriptionTransaction::create([
                'userId' => $request->userId,
                'packageId' => $request->packageId,
                'status' => 0,
           ]);

            $packageId = $request->packageId;
            $package = Package::find($packageId);

            $payment_data = [
                "orderId" => $transaction->id,
                "amount" => $package->price,
            ];

            $production_url=PaymentGateway::getProdUrl($payment_data);
            $dataArray = [
                "orderId" => $transaction->id,
                "productionUrl" => $production_url,
            ];

            return response()->json(['status' => 'success', 'message' => 'Payment gateway Url', 'result' => $dataArray], 200);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
            return response()->json(['status' => 'error', 'message' => "Server Error!"], 404);
        }
    }


    public function responseHandler(Request $request){

        //store the payment log
        PaymentGateway::mantainPaymentTransactionsLog($request);

        $orderID = $request->order_id;
        $orderStatus = $request->order_status;
        $amount = $request->amount;
        try{
        if($orderStatus=="Success"){
            $failuremessage = null;
            //get the transaction details
            $transaction = SubscriptionTransaction::find($orderID);
            $package_id = $transaction->packageId;
            $userId = $transaction->userId;
            $package = Package::find($package_id);
            $package_features = $package->features;
    
            //assign the credits to the user
            CampaignCredits::create([
                'id' => \DB::raw('gen_random_uuid()'),
                'assignedTo' => $userId,
                'sms' => $package->sms,
                'whatsapp' => $package->whatsapp,
                'app_notifications' => $package->app_notifications,
                'ads' => $package->ads,
                'createdAt' => now(),
                'updatedAt' => now(),
            ]);
           
            //mark the payment successfull
            $transaction->status = 1;
            $transaction->save;
        }

       }  catch (\Exception $e) {
         LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
         return response()->json(['status' => 'error', 'message' => "Server Error!"], 404);
       }
    }
    

     /**
     * @OA\Post(
     *     path="/api/subscription/PaymentStatus",
     *     summary="Check Payment Status",
     *     tags={"Subscription"},
     *     @OA\RequestBody(
     *      required=true,
     *      description="Enter Subscription",
     *     @OA\JsonContent(
     *         required={"userId","packageId"},
     *         @OA\Property(property="orderId", type="uuid", example="9ac6a911-f106-46e6-ac6d-516347af937d"),
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
    public function paymentStatus(Request $request){
    try{
        $orderId = $request->orderId;
        $payment = SubscriptionPaymentLog::where('order_id', $orderId)->first();
        $transactionInitiation = SubscriptionTransaction::find($orderId);
        if($transactionInitiation){
            $status_code = 200;
            $status_message = "Payment Pending";
            if($payment){
                $status_code = $payment->status_code;
                $status_message = $payment->status_message;
            }
            $dataArray = [
                "status_code" => $status_code,
                "status_message" => $status_message
            ];
            return response()->json(['status' => 'success', 'message' => 'Payment Response', 'result' => $dataArray], 200);
        }
        return response()->json(['status' => 'error', 'message' => "Transaction doesnot exist"], 404);
        }  catch (\Exception $e) {
          LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
          return response()->json(['status' => 'error', 'message' => "Server Error!"], 404);
      }
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    
}