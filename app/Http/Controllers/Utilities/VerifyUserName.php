<?php

namespace App\Http\Controllers\Utilities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**  
 * This class is responsible for retrieving the account name associated with a given account number and teleco.
 * It uses the Paystack API to resolve the account number and retrieve the account name. 
 * @link https://paystack.com/docs/identity-verification/verify-account-number/#resolve-account-number
 * @link https://paystack.com/docs/identity-verification/
 */
class VerifyUserName extends Controller
{
    private $accountName;
    private $accountNumber;
    public $teleco;
    public function __construct($accountNumber, $teleco = null)
    {
        $this->accountNumber = $accountNumber;
        $this->teleco = $teleco;
    }

    private function removeCountryCode()
    {
        // Check if the phone number starts with '+233' or '233'
        if (strpos($this->accountNumber, '+233') === 0 || strpos($this->accountNumber, '233') === 0) {
            // Remove '+233' or '233' from the beginning of the phone number
            $this->accountNumber = ltrim($this->accountNumber, '+233');
            $this->accountNumber = ltrim($this->accountNumber, '233');
        }

        // return $this->$accountNumber;
        return $this->accountNumber;
    }
    private function detectTelecomOperator()
    {
        $this->accountNumber = $this->removeCountryCode();
        if (!is_numeric($this->accountNumber)) {
            return false;
        }

        // Extract the first three digits from the phone number
        $prefix = substr($this->accountNumber, 0, 3);

        // Define telecom operator codes and their corresponding names
        $telecomOperators = [
            '023' => 'Glo',
            '024' => 'MTN',
            '025' => 'MTN',
            '053' => 'MTN',
            '054' => 'MTN',
            '055' => 'MTN',
            '059' => 'MTN',
            '027' => 'ATL',
            '057' => 'ATL',
            '026' => 'ATL',
            '056' => 'ATL',
            '028' => 'Expresso',
            '020' => 'VOD',
            '050' => 'VOD',
            // Add more operators as needed
        ];

        // Check if the prefix is in the defined list
        if (array_key_exists($prefix, $telecomOperators)) {
            return $telecomOperators[$prefix];
        }

        // Default case if the prefix doesn't match any known operator
        return false;
    }


    /**
     * Retrieves the account name associated with the given account number and teleco.
     * 
     * @param string $accountNumber The account number
     * @param string $teleco The teleco code
     * @return string The JSON-encoded response containing the account name, success status, message, and code
     * @link https://paystack.com/docs/identity-verification/verify-account-number/#resolve-account-number
     */
    public function getAccountName()
    {
        $teleco = $this->detectTelecomOperator();
        $apiURL = env('PAYSTACK_API_URL', 'https://api.paystack.co/') . "bank/resolve?account_number={$this->accountNumber}&bank_code=$teleco";
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY', 'sk_live_6754412aac8d1e5acf8e95d99e3c4229e482e0b4'),
                "Cache-Control: no-cache",
            ),
        ));
    
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        if ($statusCode >= 200 && $statusCode < 300) {
            $results = json_decode($response);
            $accountName = $results->data->account_name ?? null;
    
            return [
                'success' => true,
                'message' => $accountName,
                'code' => $statusCode
            ];
        } else {
            $errorResults = json_decode($response);
            $errorMessage = $errorResults->message ?? "Could not confirm account name with number {$this->accountNumber}.";
    
            return [
                'success' => false,
                'message' => $errorMessage,
                'code' => $statusCode
            ];
        }
    }
    
    public function verifyAccountName()
    { 
        $apiURL = env('PAYSTACK_API_URL', 'https://api.paystack.co/') . "bank/resolve?account_number={$this->accountNumber}&bank_code={$this->teleco}";
    
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $apiURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . env('PAYSTACK_SECRET_KEY', 'sk_live_6754412aac8d1e5acf8e95d99e3c4229e482e0b4'),
                "Cache-Control: no-cache",
            ),
        ));
    
        $response = curl_exec($curl);
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
    
        if ($statusCode >= 200 && $statusCode < 300) {
            $results = json_decode($response);
            $accountName = $results->data->account_name ?? null;
    
            return [
                'success' => true,
                'message' => $accountName,
                'code' => $statusCode
            ];
        } else {
            $errorResults = json_decode($response);
            $errorMessage = $errorResults->message ?? "Could not confirm account name with number {$this->accountNumber}.";
    
            return [
                'success' => false,
                'message' => $errorMessage,
                'code' => $statusCode
            ];
        }
    }
}
