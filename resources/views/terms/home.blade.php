@extends('layouts.app')

@section('title', 'CFS - Terms & Conditions')
@section('body_class', 'home-screen home-terms-screen')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/terms-modal.css') }}">
@endpush

@section('content')
    @include('home.content')
    <dialog class="home-terms-dialog" aria-label="Terms & Conditions" data-home-terms-dialog data-home-url="{{ route('home') }}">
        @include('terms.content')
    </dialog>
    <script src="{{ asset('js/terms-modal.js') }}" defer></script>
@endsection
