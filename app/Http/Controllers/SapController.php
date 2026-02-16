<?php

namespace App\Http\Controllers;


use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class SapController extends Controller
{
    public function customerSync(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                'max:20',

                'regex:/^(5|05)\d{8}$/',
            ],
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'nullable|email|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'response_code' => 'VALIDATION_ERROR',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // $phone = trim($data['phone']);
        // if (preg_match('/^5\d{8}$/', $phone)) {
        //     $phone = '0' . $phone;
        // }

        // Check existing customer
        $existing = Customer::where('phone', $data['phone'])->first();
        if ($existing) {
            return response()->json([
                'status' => 200,
                'response_code' => 'CUSTOMER_ALREADY_EXISTS',
                'message' => 'Customer exists',
                'data' => [
                    'customer_id' => $existing->id,
                ],
            ], 200);
        }

        //  Generate password
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $plainPassword = '';
        for ($i = 0; $i < 10; $i++) {
            $plainPassword .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        $customer = Customer::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'] ?? null,
            'phone'      => $data['phone'],
            'email'      => $data['email'] ?? null,
            'password'   => Hash::make($plainPassword),
            'authorized' => 1,
            'activated'  => 1,
            'otp'        => null,
        ]);

        //  Send SMS
        $appLink = (string) config('app.app_download_link');
        $smsText = "Welcome!\nLogin: {$data['phone']}\nPassword: {$plainPassword}\nDownload the app: {$appLink}";

        $smsResp = Http::withBasicAuth(
            (string) config('services.4jawaly.key'),
            (string) config('services.4jawaly.secret')
        )->post('https://api-sms.4jawaly.com/api/v1/account/area/sms/send', [
            'messages' => [[
                'text' => $smsText,
                'numbers' => [$data['phone']],
                'sender' => (string) config('services.4jawaly.sender', 'SamnanCo'),
            ]],
        ]);

        return response()->json([
            'status' => 201,
            'response_code' => 'CUSTOMER_CREATED_AND_SMS_SENT',
            'message' => 'Created and sent SMS',
            'data' => [
                'customer_id' => $customer->id,
                'sms_ok' => $smsResp->successful(),
                'sms_http_status' => $smsResp->status(),
            ],
        ], 201);
    }
}
