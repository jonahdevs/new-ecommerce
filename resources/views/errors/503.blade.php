@php $detail = isset($exception) ? trim($exception->getMessage()) : ''; @endphp

<x-error-page
    code="503"
    title="Be right back"
    heading="We’ll be right back"
    :message="($detail !== '' && $detail !== 'Service Unavailable') ? $detail : 'We’re carrying out some quick maintenance and will be back online shortly. Thanks for your patience.'"
    :standalone="true" />
