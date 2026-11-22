@php
    $public=request()->is('/');
    $locale=$public && request('lang')==='en'?'en':'ar';
    $base=rtrim(config('app.url'),'/');
    $title=$locale==='ar'?'Nursery | يوم حضانة أهدأ، وإدارة أوضح':'Nursery | A calmer nursery day';
    $description=$locale==='ar'?'إدارة الحضور والرعاية والقبول والرسوم في مساحة عربية واحدة للحضانة.':'Attendance, care, admissions and fee records in one Arabic-first nursery workspace.';
@endphp
<!DOCTYPE html>
<html lang="{{ $public ? $locale : str_replace('_', '-', app()->getLocale()) }}" @if($public) dir="{{ $locale==='ar'?'rtl':'ltr' }}" @endif>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ $public ? $title : config('app.name', 'Nursery') }}</title>
        @if($public)
        <meta name="description" content="{{ $description }}" inertia="description">
        <link rel="canonical" href="{{ $base.'/?lang='.$locale }}">
        <link rel="alternate" hreflang="ar" href="{{ $base.'/?lang=ar' }}">
        <link rel="alternate" hreflang="en" href="{{ $base.'/?lang=en' }}">
        <link rel="alternate" hreflang="x-default" href="{{ $base.'/' }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $title }}">
        <meta property="og:description" content="{{ $description }}">
        <meta property="og:url" content="{{ $base.'/?lang='.$locale }}">
        <meta property="og:image" content="{{ $base.'/brand/nursery-icon.png' }}">
        <meta name="twitter:card" content="summary">
        <script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'SoftwareApplication','name'=>'Nursery','url'=>$base.'/','description'=>$description,'applicationCategory'=>'BusinessApplication','operatingSystem'=>'Web','inLanguage'=>['ar','en']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP) !!}</script>
        @else
        <meta name="robots" content="noindex,nofollow,noarchive">
        @endif
        <link rel="icon" type="image/svg+xml" href="/brand/nursery-icon.svg">
        <link rel="icon" type="image/png" sizes="32x32" href="/brand/nursery-favicon-32.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/brand/nursery-touch-180.png">
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @if($public)
        <noscript><main style="max-width:48rem;margin:4rem auto;padding:1.5rem;color:#285d4d;font-family:system-ui,sans-serif;line-height:1.9">
            <img src="/brand/nursery-icon.png" width="96" height="96" alt="Nursery">
            <h1>{{ $title }}</h1><p>{{ $description }}</p>
            <p>{{ $locale==='ar'?'ملفات الأطفال وأولياء الأمور، حضور يومي، يوميات الرعاية، وسجلات الرسوم والتحصيل. صلاحيات منفصلة للمالك والإدارة والمعلمين.':'Child and guardian records, daily attendance, care updates, and tuition and recorded payments. Separate access for owners, managers and teachers.' }}</p>
            <a href="{{ $base.'/?lang='.($locale==='ar'?'en':'ar') }}">{{ $locale==='ar'?'English':'العربية' }}</a>
        </main></noscript>
        @endif
        @inertia
    </body>
</html>
