<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Kanallar:</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f3f4f6; text-align: left; }
        .actions { margin-bottom: 16px; }
        .btn { display: inline-block; padding: 8px 12px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; }
        .status { margin-bottom: 12px; color: #065f46; background: #d1fae5; padding: 8px 12px; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body>
    <h1>Telegram Kanallar:</h1>

    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif

    <div class="actions">
        <a class="btn" href="{{ route('telegram.channels.create') }}">Kanal qo'shish</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID:</th>
                <th>Kanal Username @</th>
                <th>Kanal ID</th>
                <th>Turi</th>
                
            </tr>
        </thead>
        <tbody>
        @forelse ($channels as $i => $ch)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $ch->username ?? '-' }}</td>
                <td>{{ $ch->channel_id }}</td>
                <td>
                    @if ($ch->is_target)
                        shaxiy ✅
                    @elseif ($ch->is_source)
                        shaxsiy emas 🔁
                    @else
                        -
                    @endif
                </td>
                
            </tr>
        @empty
            <tr><td colspan="5">No channels yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>

