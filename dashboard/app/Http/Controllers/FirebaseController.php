<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FirebaseController extends Controller
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = app('firebase.messaging');
    }

    public function subscribeToTopic(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'topic' => 'required|string',
        ]);

        $token = $request->input('token');
        $topic = $request->input('topic');

        try {
            $messaging = $this->messaging ?: app('firebase.messaging');
            if ($messaging) {
                $messaging->subscribeToTopic($topic, $token);
                return response()->json(['message' => translate('Successfully subscribed to topic')], 200);
            }
            return response()->json(['message' => translate('Firebase messaging is not configured')], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
