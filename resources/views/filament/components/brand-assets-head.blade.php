@php
    $svgFavicon = (string) env('APP_FAVICON_SVG', env('APP_FAVICON', 'images/favicon.svg'));
    $icoFavicon = (string) env('APP_FAVICON_ICO', 'favicon.ico');
    $png32Favicon = (string) env('APP_FAVICON_PNG_32', 'favicon.ico');
    $appleTouchIcon = (string) env('APP_APPLE_TOUCH_ICON', 'favicon.ico');
@endphp

<link rel="icon" type="image/svg+xml" href="{{ asset($svgFavicon) }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset($png32Favicon) }}">
<link rel="icon" type="image/x-icon" href="{{ asset($icoFavicon) }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset($appleTouchIcon) }}">

<style>
    .fi-simple-header .fi-logo {
        height: 2.25rem;
        width: auto;
        max-width: min(18rem, 72vw);
        object-fit: contain;
        filter: drop-shadow(0 1px 1px rgb(0 0 0 / 0.32));
    }

    .fi-topbar .fi-logo,
    .fi-sidebar-header .fi-logo {
        height: 1.9rem;
        width: auto;
        max-width: 14rem;
        object-fit: contain;
    }

    @media (max-width: 768px) {
        .fi-topbar .fi-logo,
        .fi-sidebar-header .fi-logo {
            height: 1.65rem;
            max-width: 10.5rem;
        }

        .fi-sidebar-header {
            padding-inline: 0.75rem;
        }
    }
</style>
