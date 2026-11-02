<?php

namespace App\CentralLogics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SMSModule
{
    public static function send($receiver, $otp)
    {
        $config = self::get_settings('otp_com');
        if (isset($config) && $config['status'] == 1) {
            return self::otp_com($receiver, $otp);
        }

        $config = self::get_settings('twilio');
        if (isset($config) && $config['status'] == 1) {
            return self::twilio($receiver, $otp);
        }

        $config = self::get_settings('nexmo');
        if (isset($config) && $config['status'] == 1) {
            return self::nexmo($receiver, $otp);
        }

        $config = self::get_settings('2factor');
        if (isset($config) && $config['status'] == 1) {
            return self::two_factor($receiver, $otp);
        }

        $config = self::get_settings('msg91');
        if (isset($config) && $config['status'] == 1) {
            return self::msg_91($receiver, $otp);
        }

        $config = self::get_settings('signal_wire');
        if (isset($config) && $config['status'] == 1) {
            return self::signal_wire($receiver, $otp);
        }

        $config = self::get_settings('alphanet_sms');
        if (isset($config) && $config['status'] == 1) {
            return self::alphanet_sms($receiver, $otp);
        }

        return 'not_found';
    }

    public static function otp_com($receiver, $otp): string
    {
        $config = self::get_settings('otp_com');
        $response = 'error';

        if (isset($config) && $config['status'] == 1) {
            $apiKey = trim($config['api_key'] ?? '');
            if (empty($apiKey)) {
                Log::warning('OTP.com error: API Key is not configured.');
                return 'error';
            }

            // Ensure phone number format is E.164 (starts with +)
            $phone = trim($receiver);
            if (!str_starts_with($phone, '+')) {
                $phone = '+' . ltrim($phone, '+');
            }

            $locale = !empty($config['locale']) ? trim($config['locale']) : 'ar';
            $clientIp = request()?->ip();

            $payload = [
                'recipient' => $phone,
                'locale' => $locale,
            ];

            if (!empty($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP)) {
                $payload['client_ip'] = $clientIp;
            }

            try {
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://api.otp.com/api/v1/otp/send',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload),
                    CURLOPT_TIMEOUT => 20,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                ]);

                $result = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);

                if (!$err && ($httpCode == 200 || $httpCode == 201)) {
                    $data = json_decode($result, true);
                    if (isset($data['otp_id'])) {
                        $otpId = $data['otp_id'];

                        // Cache otp_id for 10 minutes like AYADI implementation
                        Cache::put('otp_com:id:' . $phone, $otpId, now()->addMinutes(10));
                        Cache::put('otp_com:id:' . $receiver, $otpId, now()->addMinutes(10));
                        Cache::put('otp_com:id:' . ltrim($phone, '+'), $otpId, now()->addMinutes(10));

                        // Store the otp_id in phone_verifications table prefixed with otp_com_
                        DB::table('phone_verifications')
                            ->where('phone', $receiver)
                            ->orWhere('phone', $phone)
                            ->orWhere('phone', ltrim($phone, '+'))
                            ->update([
                                'token' => 'otp_com_' . $otpId,
                                'updated_at' => now(),
                            ]);

                        // Also update password_resets table if applicable
                        DB::table('password_resets')
                            ->where('email_or_phone', $receiver)
                            ->orWhere('email_or_phone', $phone)
                            ->orWhere('email_or_phone', ltrim($phone, '+'))
                            ->update([
                                'token' => 'otp_com_' . $otpId,
                            ]);

                        Log::info("[OtpCom] OTP dispatched successfully to [{$phone}], OTP ID: [{$otpId}]");
                        $response = 'success';
                    }
                } else {
                    Log::error('OTP.com Send API Error: HTTP ' . $httpCode . ' Body: ' . $result . ' Curl Error: ' . $err);
                    $response = 'error';
                }
            } catch (\Exception $exception) {
                Log::error('OTP.com Send Exception: ' . $exception->getMessage());
                $response = 'error';
            }
        }

        return $response;
    }

    public static function verify_otp_com($otpId, $code): bool
    {
        $config = self::get_settings('otp_com');
        if (!isset($config) || empty($config['api_key'])) {
            return false;
        }

        $apiKey = trim($config['api_key']);

        // Support passing either raw UUID, otp_com_UUID, or phone number (from Cache)
        if (str_starts_with($otpId, 'otp_com_')) {
            $otpId = substr($otpId, 8);
        } elseif (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $otpId)) {
            $cached = Cache::get('otp_com:id:' . $otpId);
            if ($cached) {
                $otpId = $cached;
            }
        }

        try {
            $payload = [
                'otp_id' => $otpId,
                'code' => (string)$code,
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://api.otp.com/api/v1/otp/verify',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_TIMEOUT => 15,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && ($httpCode == 200 || $httpCode == 201)) {
                $data = json_decode($result, true);
                if (isset($data['matched']) && $data['matched'] === true) {
                    return true;
                }
                if (isset($data['status']) && $data['status'] === 'approved') {
                    return true;
                }
            } else {
                Log::error('OTP.com Verify API Error: HTTP ' . $httpCode . ' Body: ' . $result . ' Curl Error: ' . $err);
            }
        } catch (\Exception $exception) {
            Log::error('OTP.com Verify Exception: ' . $exception->getMessage());
        }

        return false;
    }

    public static function twilio($receiver, $otp): string
    {
        $config = self::get_settings('twilio');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $sid = $config['sid'];
            $token = $config['token'];
            try {
                $twilio = new Client($sid, $token);
                $twilio->messages
                    ->create($receiver, // to
                        array(
                            "messagingServiceSid" => $config['messaging_service_sid'],
                            "body" => $message
                        )
                    );
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function nexmo($receiver, $otp): string
    {
        $config = self::get_settings('nexmo');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            try {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://rest.nexmo.com/sms/json');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, "from=".$config['from']."&text=".$message."&to=".$receiver."&api_key=".$config['api_key']."&api_secret=".$config['api_secret']);

                $headers = array();
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                $result = curl_exec($ch);
                if (curl_errno($ch)) {
                    echo 'Error:' . curl_error($ch);
                }
                curl_close($ch);
                $response = 'success';
            } catch (\Exception $exception) {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function two_factor($receiver, $otp): string
    {
        $config = self::get_settings('2factor');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $apiKey = $config['api_key'];
            $otpTemplate = $config['otp_template'] ?? '';
            $apiUrl = "https://2factor.in/API/V1/$apiKey/SMS/$receiver/$otp/$otpTemplate";

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function msg_91($receiver, $otp): string
    {
        $config = self::get_settings('msg91');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.msg91.com/api/v5/otp?template_id=" . $config['template_id'] . "&mobile=" . $receiver . "&authkey=" . $config['auth_key'] . "",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_POSTFIELDS => "{\"OTP\":\"$otp\"}",
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/json"
                ),
            ));
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function signal_wire($receiver, $otp): string
    {
        $config = self::get_settings('signal_wire');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {

            $message = str_replace("#OTP#", $otp, "Your otp is #OTP#.");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://" . $config['space_url'] . "/api/laml/2010-04-01/Accounts/" . $config['project_id'] . "/Messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $config['project_id'] . ':' . $config['token']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, "From=" . $config['from'] . "&To=" . $receiver . "&Body=" . $message);

            $response = curl_exec($ch);
            $error = curl_error($ch);

            curl_close($ch);

            if (!$error) {
                $response = 'success';
            } else {
                $response = 'error';
            }

        }
        return $response;
    }

    public static function alphanet_sms($receiver, $otp): string
    {
        $config = self::get_settings('alphanet_sms');
        $response = 'error';
        if (isset($config) && $config['status'] == 1) {
            $receiver = str_replace("+", "", $receiver);
            $message = str_replace("#OTP#", $otp, $config['otp_template']);
            $api_key = $config['api_key'];

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.sms.net.bd/sendsms',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => array('api_key' => $api_key, 'msg' => $message, 'to' => $receiver),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);

            if (!$err) {
                $response = 'success';
            } else {
                $response = 'error';
            }
        }
        return $response;
    }

    public static function get_settings($name)
    {
        $config = DB::table('addon_settings')->where('key_name', $name)
            ->where('settings_type', 'sms_config')->first();

        if (isset($config) && !is_null($config->live_values)) {
            return json_decode($config->live_values, true);
        }
        return null;
    }
}
