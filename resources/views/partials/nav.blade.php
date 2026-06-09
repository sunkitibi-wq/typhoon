<nav class="bg-card border-b border-border shadow-sm mb-6">
    <div class="container mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-8">
            <a href="/" class="text-xl font-bold tracking-wider text-primary">TYPHOON BANKING</a>
            @auth
                <div class="flex space-x-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-foreground hover:text-primary transition">Admin Dashboard</a>
                    @else
                        <a href="{{ route('client.home') }}" class="text-sm font-medium text-foreground hover:text-primary transition">Client Portal</a>
                    @endif
                </div>
            @endauth
        </div>
        <div class="flex items-center space-x-4">
            @auth
                <span class="text-sm text-muted-foreground">Welcome, <strong>{{ auth()->user()->name }}</strong></span>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-red-500 hover:text-red-400 transition">
                        Log out
                    </button>
                </form>
            @else
                <a href="/login" class="text-sm font-semibold text-primary hover:text-primary-hover transition">Log in</a>
            @endauth
        </div>
    </div>
</nav>
