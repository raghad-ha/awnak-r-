<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\JoinRequest;
use App\Models\JoinRequestFile;

class JoinRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        // only pending accounts should submit join requests
        if ($user->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Join request can be submitted only when status is pending.',
                'data' => ['status' => $user->status],
            ], 403);
        }

        $data = $request->validate([
            'notes' => ['nullable','string','max:5000'],
            'files' => ['required','array','min:1'],
            'files.*.file' => ['required','file','max:10240'], // 10MB
            'files.*.file_type' => ['required','string','max:50'],
        ]);

        $requesterType = $user->type; // volunteer or organization

        $joinRequest = DB::transaction(function () use ($user, $data, $requesterType) {
            // optional: block duplicate pending request
            $existing = JoinRequest::where('requester_user_id', $user->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            if ($existing) {
                return $existing;
            }

            $jr = JoinRequest::create([
                'requester_user_id' => $user->id,
                'requester_type' => $requesterType,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['files'] as $item) {
                $path = $item['file']->store('join_requests', 'public');

                JoinRequestFile::create([
                    'join_request_id' => $jr->id,
                    'file_path' => $path,
                    'file_type' => $item['file_type'],
                ]);
            }

            return $jr;
        });

        return response()->json([
            'success' => true,
            'message' => 'Join request submitted.',
            'data' => [
                'join_request' => $joinRequest->load('files'),
            ],
        ], 201);
    }

    public function myRequest(Request $request)
    {
        $user = $request->user();

        $jr = JoinRequest::where('requester_user_id', $user->id)
            ->latest('id')
            ->with('files')
            ->first();

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => [
                'join_request' => $jr,
            ],
        ]);
    }
}
