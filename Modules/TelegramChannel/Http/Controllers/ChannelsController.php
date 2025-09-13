<?php

namespace Modules\TelegramChannel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TelegramChannel;
use Illuminate\Http\Request;
use Modules\TelegramChannel\Http\Resources\TelegramChannelResource;

class ChannelsController extends Controller
{
    public function index()
    {
        $channels = TelegramChannel::query()->orderByDesc('id')->paginate(50);
        return TelegramChannelResource::collection($channels);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['nullable', 'string'],
            'channel_id' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'is_source' => ['nullable', 'boolean'],
            'is_target' => ['nullable', 'boolean'],
        ]);

        // Ensure only one target channel exists at a time (optional)
        if (!empty($data['is_target'])) {
            TelegramChannel::where('is_target', true)->update(['is_target' => false]);
        }

        $channel = TelegramChannel::updateOrCreate(
            ['channel_id' => $data['channel_id']],
            [
                'username' => $data['username'] ?? null,
                'title' => $data['title'] ?? null,
                'is_source' => $data['is_source'] ?? true,
                'is_target' => $data['is_target'] ?? false,
            ]
        );

        return new TelegramChannelResource($channel);
    }
}

