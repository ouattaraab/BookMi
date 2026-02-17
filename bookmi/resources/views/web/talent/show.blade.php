@extends('web.layouts.app')

@section('title', $profile->stage_name . ' - BookMi')
@section('meta_description'){{ Str::limit($profile->bio ?? 'Talent sur BookMi', 160) }}@endsection

@section('meta_tags')
    <meta property="og:title" content="{{ $profile->stage_name }} - BookMi">
    <meta property="og:description" content="{{ Str::limit($profile->bio ?? 'Talent sur BookMi', 160) }}">
    <meta property="og:url" content="{{ route('talent.show', $profile->slug) }}">
    <meta property="og:type" content="profile">
    <meta property="og:site_name" content="BookMi">
@endsection

@section('schema_org')
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $profile->stage_name,
        'description' => Str::limit($profile->bio ?? '', 160),
        'url' => route('talent.show', $profile->slug),
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $profile->city,
            'addressCountry' => 'CI',
        ],
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $profile->average_rating,
            'bestRating' => '5',
            'ratingCount' => '0',
        ],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_HEX_TAG) !!}
    </script>
@endsection

@section('content')
    <main>
        <h1>{{ $profile->stage_name }}</h1>
        <p>{{ $profile->category->name }}</p>
        <p>{{ $profile->city }}</p>
        @if($profile->is_verified)
            <span>Vérifié</span>
        @endif
        @if($profile->bio)
            <p>{{ $profile->bio }}</p>
        @endif
        <p>Note : {{ $profile->average_rating }}/5</p>

        @if($similarTalents->isNotEmpty())
            <section>
                <h2>Talents similaires</h2>
                @foreach($similarTalents as $similar)
                    <a href="{{ route('talent.show', $similar->slug) }}">{{ $similar->stage_name }}</a>
                @endforeach
            </section>
        @endif
    </main>
@endsection
