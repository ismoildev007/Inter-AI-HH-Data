@php
    $title = $title ?? '';
    $company = $company ?? '';
    $phones = $phones ?? [];
    $usernames = $usernames ?? [];
    $description = $description ?? '';
    $sourceLink = $source_link ?? null;
    $plainSource = $plain_username ?? null;
    $targetUsername = $target_username ?? null;

    $contactParts = [];
    foreach ($usernames as $u) {
        $u = '@' . ltrim((string) $u, '@');
        $contactParts[] = e($u);
    }
    foreach ($phones as $p) {
        $contactParts[] = e($p);
    }
    $contactLine = trim(implode(' ', array_filter($contactParts)));
    $targetLink = $targetUsername ? ('https://t.me/'.ltrim($targetUsername, '@')) : null;
@endphp

🫡title: {{ e($title) }}
🏢company: {{ e($company) }}
📞contact: {{ $contactLine }}
📝description: {{ e($description) }}
@if($sourceLink && $plainSource)
🔗manba: <a href="{{ $sourceLink }}">{{ '@'.$plainSource }}</a>
@endif
@if($targetLink && $targetUsername)
✅Bizning kanal: <a href="{{ $targetLink }}">{{ '@'.ltrim($targetUsername, '@') }}</a>
@endif
