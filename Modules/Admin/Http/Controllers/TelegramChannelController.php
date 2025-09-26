<?php

namespace Modules\Admin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TelegramChannel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TelegramChannelController extends Controller
{
    /**
     * Telegram channels list.
     */
    public function index()
    {
        $channels = TelegramChannel::query()
            ->orderByDesc('id')
            ->get(['id', 'channel_id', 'username', 'is_source', 'is_target', 'last_message_id']);

        return view('admin::TelegramChannels.index', compact('channels'));
    }

    /**
     * Create telegram channel form.
     */
    public function create()
    {
        return view('admin::TelegramChannels.create');
    }

    /**
     * Store a telegram channel.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel_id' => ['required', 'string'],
            'username' => ['nullable', 'string'],
            'is_source' => ['nullable', 'boolean'],
            'is_target' => ['nullable', 'boolean'],
            'role' => ['nullable', 'in:source,target'],
        ]);

        // Support either checkboxes or a single role radio select
        $isSource = (bool) ($data['is_source'] ?? false);
        $isTarget = (bool) ($data['is_target'] ?? false);
        if (isset($data['role'])) {
            $isSource = $data['role'] === 'source';
            $isTarget = $data['role'] === 'target';
        }

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

        return redirect()->route('admin.telegram_channels.index')
            ->with('status', 'Channel saved: #'.$channel->id);
    }

    /**
     * Delete a telegram channel.
     */
    public function destroy(TelegramChannel $channel): RedirectResponse
    {
        $id = $channel->id;
        $channel->delete();

        return redirect()->route('admin.telegram_channels.index')
            ->with('status', "Channel #{$id} deleted.");
    }
}
