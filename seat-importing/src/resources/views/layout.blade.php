@extends('web::layouts.app')

@section('title', 'Market Importing')

@section('content')
    @yield('seat-importing-content')
@endsection

@push('javascript')
    @yield('seat-importing-js')
@endpush
