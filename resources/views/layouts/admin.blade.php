// resources/views/layouts/admin.blade.php
@extends('layouts.stitch')

@section('title', 'Admin Dashboard')

@section('content')
    @include('components.card')
    <h1 class="text-2xl font-bold mb-4">Admin Dashboard</h1>
    <p>Welcome to the admin area.</p>
@endsection
