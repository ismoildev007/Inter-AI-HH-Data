<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Channel</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; margin: 24px; }
        label { display: block; margin-top: 12px; }
        input[type=text] { width: 420px; padding: 8px; }
        .row { margin-bottom: 8px; }
        .btn { display: inline-block; padding: 8px 12px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; border: 0; cursor: pointer; }
        .error { color: #991b1b; background: #fee2e2; padding: 8px 12px; border-radius: 4px; margin-bottom: 12px; display: inline-block; }
        .back { margin-right: 8px; }
    </style>
</head>
<body>
    <h1>Create Channel</h1>

    @if ($errors->any())
        <div class="error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="post" action="{{ route('telegram.channels.store') }}">
        @csrf

        <div class="row">
            <label>Kanal Username</label>
            <input type="text" name="username" value="{{ old('username') }}" placeholder="@ belgisini qo'ymasdan o'zini yozing">
        </div>
                <div class="row">
            <label>Kanal ID: @ va https/t.me/ kerak emas shunchaki username yoki id raqam,
                 -100 ni boshiga qo'shib yozish shart</label>
            <input type="text" name="channel_id" value="{{ old('channel_id') }}" placeholder="-100 bilan yozasiy Kanal id ni, username, yoki telegram link" required>
        </div>
        <div class="row">
            <label><input type="checkbox" name="is_source" value="1" {{ old('is_source') ? 'checked' : '' }}> Shaxsiy emas🔁</label>
            <label><input type="checkbox" name="is_target" value="1" {{ old('is_target') ? 'checked' : '' }}> Shaxsiy ✅</label>
        </div>
        <div class="row">
            <a class="btn back" href="{{ route('telegram.channels.index') }}">Back</a>
            <button class="btn" type="submit">Save</button>
        </div>
    </form>
</body>
</html>

