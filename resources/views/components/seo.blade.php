@props([
    'title' => 'GreensWell - SMS Messaging Made Simple',
    'description' => 'Powerful SMS platform designed to help you connect with your audience through reliable, fast, and affordable messaging. Send SMS messages via API or web interface.',
    'keywords' => 'SMS, SMS API, SMS Gateway, Bulk SMS, SMS Service, Uganda SMS, Africa SMS, Text Messaging, SMS Platform',
    'image' => asset('logo.png'),
    'url' => url()->current(),
    'type' => 'website',
    'siteName' => 'GreensWell'
])

<!-- Primary Meta Tags -->
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="GreensWell">
<meta name="robots" content="index, follow">
<meta name="language" content="English">
<meta name="revisit-after" content="7 days">
<link rel="canonical" href="{{ $url }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $url }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $image }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="en_US">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ $url }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $image }}">
<meta name="twitter:creator" content="@GreensWell">

<!-- Additional Meta Tags -->
<meta name="theme-color" content="#004f78">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="GreensWell">
