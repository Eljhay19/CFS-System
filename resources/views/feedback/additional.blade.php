@extends('layouts.app')
@section('title', $steps[$step]['title'])
@section('body_class', 'feedback-screen')
@section('content')
    @include('feedback.partials.step')
@endsection
