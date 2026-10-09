@php
    $logo = $advert->mediaUrl($advert->logo_path);
    $creative = $advert->mediaUrl($advert->creative_path);
@endphp
<div class="space-y-4">
    <div class="flex items-center gap-3">
        @if ($logo)
            <img src="{{ $logo }}" alt="" style="height: 48px; width: auto; max-width: 160px; object-fit: contain;">
        @endif
        <p class="text-base font-semibold text-gray-900">{{ $advert->advertiser_name }}</p>
    </div>
    @if ($advert->creative_type === 'video')
        <video src="{{ $creative }}" muted autoplay playsinline controls style="width: 100%; max-height: 360px; object-fit: contain; background: #111827;"></video>
    @elseif ($creative)
        <img src="{{ $creative }}" alt="" style="width: 100%; max-height: 360px; object-fit: contain; background: #f8fafc;">
    @endif
    <p class="text-sm text-gray-600">Your dashboard opens in 10 seconds.</p>
    <p class="text-xs text-gray-500">{{ $advert->timezone }} · {{ $advert->scheduleLabel() }}</p>
</div>
