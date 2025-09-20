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
    // HTML parse_mode: escape only &, <, > to keep quotes intact for Uzbek o'
    $esc = fn($s) => str_replace(['&','<','>'], ['&amp;','&lt;','&gt;'], (string) $s);
    $titleSafe = $esc($title);
    $companySafe = $esc($company);
    $descSafe = $esc($description);
@endphp

<b>Yangi bo'sh ish o'rni e'lon qilindi!</b><br>
🫡 <b>Lavozim:</b> {!! $titleSafe !!}<br>
🏢 <b>Kompaniya:</b> {!! $companySafe !!}
📞 <b>Bog’lanish:</b> {{ $contactLine }}
📝 <b>Tavsif:</b> {!! $descSafe !!}<br>
@if($sourceLink && $plainSource)
🔗 <b>Manba:</b> <a href="{{ $sourceLink }}">{{ '   ' . $plainSource . '   '}}</a><br>
@endif
@if($targetLink && $targetUsername)
✅ <b>Bizning kanal:</b> <a href="{{ $targetLink }}">{{ '@'.ltrim($targetUsername, '@') }}</a>
@endif

