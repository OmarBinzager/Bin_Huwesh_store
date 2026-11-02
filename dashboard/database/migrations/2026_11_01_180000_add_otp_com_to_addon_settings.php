<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('addon_settings')
            ->where('key_name', 'otp_com')
            ->where('settings_type', 'sms_config')
            ->exists();

        if (!$exists) {
            $defaultConfig = [
                'gateway' => 'otp_com',
                'mode' => 'live',
                'status' => 0,
                'api_key' => '',
                'locale' => 'ar',
                'otp_template' => 'Your verification code is #OTP#',
            ];

            DB::table('addon_settings')->insert([
                'id' => (string) Str::uuid(),
                'key_name' => 'otp_com',
                'live_values' => json_encode($defaultConfig),
                'test_values' => json_encode($defaultConfig),
                'settings_type' => 'sms_config',
                'mode' => 'live',
                'is_active' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('addon_settings')
            ->where('key_name', 'otp_com')
            ->where('settings_type', 'sms_config')
            ->delete();
    }
};
