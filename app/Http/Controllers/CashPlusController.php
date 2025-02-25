<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashPlusController extends Controller
{

      //Génère un token de paiement.
        public function generateToken(Request $request)
        {
            try{
                $request_id = uniqid();
                $amount = (string) $request->input('amount'); // Cast en string
                $fees = 0; // Pas de frais
                $marchand_code = env('CASHPLUS_MERCHANT_CODE');
                $secret_key = env('CASHPLUS_SECRET_KEY');
                $username = $request->input('username');
                
                $hmac = strtoupper(hash('sha256', $marchand_code . $secret_key . $amount));
                
                $payload = [
                    'request_id' => $request_id,
                    'amount' => $amount,
                    'date_expiration' => "", // Vide comme l'exemple donné
                    'fees' => $fees,
                    'marchand_code' => $marchand_code,
                    'hmac' => $hmac,
                    'json_data' => !empty($username) ? json_encode([['key' => 'username', 'value' => $username]]) : "" // Vérifier si json_data doit être vide
                ];
                
                $response = Http::withHeaders([
                    'User-Agent' => 'LaravelHttpClient',
                    'Accept' => 'application/json'
                ])->post(env('CASHPLUS_API_URL') . 'generate_token', $payload);
                    dd($response);
                // Gérer la réponse
                if ($response->successful()) {
                    $responseData = $response->json();
                    if ($responseData['SUCCESS'] == 1) {
                        return response()->json([
                            'success' => true,
                            'token' => $responseData['TOKEN'],
                            'date_expiration' => $responseData['DATE_EXPIRATION']
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $responseData['MESSAGE'] ?? 'Erreur inconnue'
                        ], 400);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Erreur de connexion à l’API CashPlus'
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur inconnue'
                ], 500);
            }
        }



        // to check the status of the token if user has paid or not
        public function statusToken(Request $request)
        {
            // Retrieve the token and marchand code from the request
            $token = $request->input('token');
            $marchand_code = env('CASHPLUS_MERCHANT_CODE');  // Store your CashPlus merchant code in .env
            $secret_key = env('CASHPLUS_SECRET_KEY');  // Store your CashPlus secret key in .env

            // Create the HMAC hash
            $hmac = strtoupper(hash('sha256', $marchand_code . $secret_key));

            // Prepare the payload to send in the POST request
            $payload = [
                'token' => $token,
                'marchand_code' => $marchand_code,
                'hmac' => $hmac,
            ];

            // Send the POST request to the CashPlus API to check token status
            $response = Http::post(env('CASHPLUS_API_URL') . '/status_token', $payload);

            // Check if the response is successful
            if ($response->successful()) {
                // Return the response as JSON
                return response()->json($response->json());
            } else {
                // In case of failure, return error message
                return response()->json([
                    'message' => 'Erreur lors de la vérification du statut du token.',
                    'error' => $response->body(),
                ], 400);
            }
        }


        // Handle the callback from CashPlus to now if the payment was successful or not
        public function handleCallback(Request $request)
        {
            // Get the request_id and hmac from the callback data
            $request_id = $request->input('request_id');
            $hmac = $request->input('hmac');
            $secret_key = env('CASHPLUS_SECRET_KEY'); // Get your secret key from .env

            // Generate the HMAC using request_id and secret_key
            $generated_hmac = strtoupper(hash('sha256', $request_id . $secret_key));

            // Check if the received HMAC matches the generated HMAC
            if ($hmac === $generated_hmac) {
                // Callback is valid, process the payment or take necessary action
                // For example, update the order status, send a confirmation email, etc.
                Log::info("Callback valid for request_id: $request_id");

                // Respond to CashPlus with "OK" to acknowledge the valid callback
                return response('OK', 200);
            } else {
                // Callback is invalid, log it for further investigation
                Log::warning("Invalid callback for request_id: $request_id");

                // Respond with "NOK" to indicate the callback was not valid
                return response('NOK', 400);
            }
        }


        // Get the status of tokens for a specific period , to know if the users has paid or not , he returns a list of tokens with their status
        public function tokensStatusForPeriod(Request $request)
        {
            // Retrieve the date_request from the request input (format: 'yyyy-MM-dd HH:mm:ss')
            $date_request = $request->input('date_request');
            $marchand_code = env('CASHPLUS_MERCHANT_CODE'); // Merchant code from .env
            $secret_key = env('CASHPLUS_SECRET_KEY'); // Secret key from .env

            // Generate the HMAC using the merchant code and secret key
            $hmac = strtoupper(hash('sha256', $marchand_code . $secret_key));

            // Prepare the payload for the API request
            $payload = [
                'date_request' => $date_request,
                'marchand_code' => $marchand_code,
                'hmac' => $hmac,
            ];

            // Send the request to the CashPlus API
            $response = Http::post(env('CASHPLUS_API_URL') . '/token_status_for_period', $payload);

            // Check the response status and return the data accordingly
            $response_data = $response->json();

            // If the response was successful, return the tokens' status
            if ($response_data['SUCCESS'] === 1) {
                return response()->json([
                    'message' => 'Tokens status retrieved successfully.',
                    'tokens_status' => $response_data['TOKENS_STATUS']
                ], 200);
            } else {
                // If there was an error (e.g., invalid date or other issues), return an error response
                return response()->json([
                    'message' => $response_data['MESSAGE'] ?? 'An error occurred.',
                    'success' => false,
                ], 400);
            }
        }


}
