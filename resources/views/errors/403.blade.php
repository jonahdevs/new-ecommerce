@php $detail = isset($exception) ? trim($exception->getMessage()) : ''; @endphp

<x-error-page
    code="403"
    title="Access denied"
    heading="You don’t have access to this page"
    :message="$detail !== '' ? $detail : 'You don’t have permission to view this page. If you think this is a mistake, please contact us.'" />
