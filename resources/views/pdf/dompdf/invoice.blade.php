@extends('pdf.dompdf.layouts.main')

@section('title', 'Tax Invoice ' . $order->reference)

@section('content')
    {{-- Top accent bar --}}
    <div class="h-1.5 bg-[#c02434] w-full"></div>

    {{-- 1. Header Section --}}
    @include('pdf.dompdf.partials.header')

    {{-- 2. Customer & Payment Info --}}
    @include('pdf.dompdf.partials.customer-payment')

    {{-- 3. Items Table --}}
    @include('pdf.dompdf.partials.items-table')

    {{-- 4. Summary Section --}}
    @include('pdf.dompdf.partials.summary')

    {{-- 5. Order Notes --}}
    @include('pdf.dompdf.partials.order-note')

    {{-- 6. Footer --}}
    @include('pdf.dompdf.partials.footer')
@endsection
