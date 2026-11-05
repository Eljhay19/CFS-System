@extends('layouts.app')

@section('title', 'CFS - Home')
@section('body_class', 'home-screen')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
    @include('home.content')
@endsection
