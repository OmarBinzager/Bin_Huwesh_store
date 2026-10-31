<?php

namespace App\Traits;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use Illuminate\Support\Carbon;

trait HelperTrait
{
    public function translate_message($language_code, $message_key)
    {
        $statusKey = Helpers::order_status_message_key($message_key);
        $translatedMessage = BusinessSetting::with('translations')->where(['key' => $statusKey])->first();
        if (!$translatedMessage) {
            return 0;
        }

        $decodedValue = is_string($translatedMessage->value) ? json_decode($translatedMessage->value, true) : $translatedMessage->value;
        // Check if the notification status is disabled (0)
        if (isset($decodedValue['status']) && (int)$decodedValue['status'] === 0) {
            return 0;
        }

        $message = '';
        if (isset($translatedMessage->translations)){
            foreach ($translatedMessage->translations as $translation){
                if ($language_code == $translation->locale){
                    $message = $translation->value;
                }
            }
        }

        // If no locale translation found, fallback to default message if enabled
        if (empty($message) && isset($decodedValue['message'])) {
            $message = $decodedValue['message'];
        }

        return !empty($message) ? $message : 0;
    }

    public function dynamic_key_replaced_message($message, $type, $order = null, $customer = null)
    {
        if (empty($message) || $message === 0 || $message === '0') {
            return 0;
        }

        $customerName = '';
        $deliverymanName = '';
        $order_id = $order ? $order->id : '';

        if ($type == 'order'){
            $deliverymanName = ($order && $order->delivery_man) ? $order->delivery_man->f_name. ' '. $order->delivery_man->l_name : '';
            $customerName = ($order && $order->is_guest == 0) ? ($order->customer ? $order->customer->f_name. ' '. $order->customer->l_name : '') : 'Guest User';
        }
        if ($type == 'wallet'){
            $customerName = $customer ? ($customer->f_name. ' '. $customer->l_name) : '';
        }
        $storeName = Helpers::get_business_settings('restaurant_name');
        $value = Helpers::text_variable_data_format(value:$message, user_name: $customerName, store_name: $storeName, delivery_man_name: $deliverymanName, order_id: $order_id);
        return $value;
    }

    public function checkMaintenanceMode(): array
    {
        $maintenanceSystemArray = ['branch_panel', 'customer_app', 'web_app', 'deliveryman_app'];
        $selectedMaintenanceSystem = Helpers::get_business_settings('maintenance_system_setup')?? [];

        $maintenanceSystem = [];
        foreach ($maintenanceSystemArray as $system) {
            $maintenanceSystem[$system] = in_array($system, $selectedMaintenanceSystem);
        }

        $maintenanceModeStatus = (bool)Helpers::get_business_settings('maintenance_mode');
        return [
            'maintenance_mode_status' => $maintenanceModeStatus,
            'selected_maintenance_system' => $maintenanceSystem
        ];
    }
}
