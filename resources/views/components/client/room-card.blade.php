@props([
    'title',
    'location',
    'price',
    'rating',
    'image',
    'features' => [],
    'badge' => null,
    'url' => null,
    'dataAttributes' => [],
])

@php
  $cardTag = $url ? 'a' : 'div';
  $cardAttributes = $url ? 'href="' . $url . '" style="text-decoration: none; color: inherit; display: block;"' : '';
  foreach($dataAttributes as $key => $value) {
    $cardAttributes .= ' data-' . $key . '="' . $value . '"';
  }
@endphp

<{{ $cardTag }} class="room-card hotel-card" {!! $cardAttributes !!}>
  <div class="card-image">
    <img src="{{ asset('assets/' . $image) }}" alt="{{ $title }}" />
    <div class="card-price">₱{{ is_numeric($price) ? number_format($price, 0) : $price }} per night</div>
    @if($badge)
      <div class="card-badge {{ strtolower($badge) }}">{{ $badge }}</div>
    @endif
  </div>
  <div class="card-content">
    <div class="card-header">
      <h3 class="card-title">{{ $title }}</h3>
      @if($rating > 0)
        <div class="card-rating">
          <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
          </svg>
          <span>{{ number_format($rating, 1) }}</span>
        </div>
      @endif
    </div>
    <p class="card-location">{{ $location }}</p>
    @if($slot->isNotEmpty())
      <p class="card-description">
        {{ $slot }}
      </p>
    @endif
    @if(count($features) > 0)
      <div class="card-features">
        @foreach($features as $feature)
          <span class="feature-tag">{{ $feature }}</span>
        @endforeach
      </div>
    @endif
    @if(!$url)
      <button class="book-now-btn" type="button">Book Now</button>
    @endif
  </div>
</{{ $cardTag }}>
