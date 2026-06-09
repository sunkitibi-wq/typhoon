@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
    @parent

    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-4 text-foreground">Platform Analytics Summary</h2>
        <div class="bg-card border border-border p-6 rounded-xl shadow-sm">
            <h3 class="text-md font-bold mb-2">Platform Control Panel</h3>
            <p class="text-sm text-muted-foreground mb-4">View global settings, approve loan applications, or review AML/compliance activity alerts.</p>
            <div class="flex space-x-4">
                <a href="/admin/platform-settings" class="text-sm font-semibold text-primary hover:text-primary-hover">Settings &rarr;</a>
                <a href="/admin/monitoring" class="text-sm font-semibold text-primary hover:text-primary-hover">AML Alerts &rarr;</a>
            </div>
        </div>
    </div>
@endsection
