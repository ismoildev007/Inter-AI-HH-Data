<?php

namespace Modules\TelegramChannel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TelegramChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChannelsPageController extends Controller
{
    public function index(): View
    {
        $channels = TelegramChannel::query()
            ->orderByDesc('id')
            ->get(['id', 'channel_id', 'username', 'is_source', 'is_target', 'last_message_id']);

        return view('telegramchannel::index', compact('channels'));
    }

    public function create(): View
    {
        return view('telegramchannel::create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_id' => ['required', 'string'],
            'username' => ['nullable', 'string'],
            'is_source' => ['nullable', 'boolean'],
            'is_target' => ['nullable', 'boolean'],
        ]);

        $isSource = (bool) ($data['is_source'] ?? false);
        $isTarget = (bool) ($data['is_target'] ?? false);

        if (!$isSource && !$isTarget) {
            return back()->withInput()->withErrors(['role' => 'Please select either Source or Target.']);
        }

        // Ensure only one target at a time
        if ($isTarget) {
            TelegramChannel::where('is_target', true)->update(['is_target' => false]);
        }

        $channel = TelegramChannel::updateOrCreate(
            ['channel_id' => $data['channel_id']],
            [
                'username' => $data['username'] ?? null,
                'is_source' => $isSource && !$isTarget,
                'is_target' => $isTarget,
            ]
        );

        return redirect()->route('telegram.channels.index')
            ->with('status', 'Channel saved: #'.$channel->id);
    }
}

