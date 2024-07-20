<?php
namespace App\Helpers;
use App\Models\PaymentLog;


class PaymentGateway {
    public static function getProdUrl($payment_data){
        $amount = $payment_data['amount'];
        $orderId = $payment_data['orderId'];
        $working_key=env('CCAVENUE_WORKING_KEY');//Shared by CCAVENUES
        $access_code=env('CCAVENUE_ACCESS_CODE');//Shared by CCAVENUES

        $merchant_data='';
        $dataArray = [
            "merchant_id" => env('CCAVENUE_MERCHANT_ID'),
            "order_id" => $orderId,
            "currency" => 'INR',
            "amount" => $amount,
            "redirect_url" => env('CCAVENUE_REDIRECT_URL'),
            "cancel_url" => env('CCAVENUE_CANCEL_URL'),
            "integration_type" => 'iframe_normal',
            "language" => 'en',

        ];
        foreach ($dataArray as $key => $value){
            $merchant_data.=$key.'='.$value.'&';
        }

        $encrypted_data=encrypt($merchant_data,$working_key); // Method for encrypting the data.
        $production_url=env('CCAVENUE_URL').'transaction/transaction.do?command=initiateTransaction&encRequest='.$encrypted_data.'&access_code='.$access_code;

        return $production_url;
    }

function encrypt($plainText,$key)
{
	$key = hextobin(md5($key));
	$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
	$openMode = openssl_encrypt($plainText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
	$encryptedText = bin2hex($openMode);
	return $encryptedText;
}

function decrypt($encryptedText,$key)
{
	$key = hextobin(md5($key));
	$initVector = pack("C*", 0x00, 0x01, 0x02, 0x03, 0x04, 0x05, 0x06, 0x07, 0x08, 0x09, 0x0a, 0x0b, 0x0c, 0x0d, 0x0e, 0x0f);
	$encryptedText = hextobin($encryptedText);
	$decryptedText = openssl_decrypt($encryptedText, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $initVector);
	return $decryptedText;
}

function hextobin($hexString) 
 { 
	$length = strlen($hexString); 
	$binString="";   
	$count=0; 
	while($count<$length) 
	{       
	    $subString =substr($hexString,$count,2);           
	    $packedString = pack("H*",$subString); 
	    if ($count==0)
	    {
			$binString=$packedString;
	    } 
	    
	    else 
	    {
			$binString.=$packedString;
	    } 
	    
	    $count+=2; 
	} 
        return $binString; 
  } 

  public function mantainPaymentTransactionsLog($request){
	PaymentLog::create([
		'order_id' => $request->order_id,
		'tracking_id' => $request->tracking_id,
		'order_status' => $request->order_status,
		'failure_message' => $request->failure_message,
		'payment_mode' => $request->payment_mode,
		'card_name' => $request->card_name,
		'status_code' => $request->status_code,
		'status_message' => $request->status_message,
		'currency' => $request->currency,
		'amount' => $request->amount,
		'billing_name' => $request->billing_name,
		'billing_tel' => $request->billing_tel,
		'billing_email' => $request->billing_email,
	]);
  }


}