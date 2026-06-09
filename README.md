# Typhoon Banking Platform — Traditional Layout Integration (Stitch)

This document provides a guide for extending, customizing, and working with the **Stitch Traditional Blade Layout System** in the Typhoon Banking Platform. While the primary banking interface is built using a modern React 19 + Inertia.js 3 single-page application stack, the platform also supports a native traditional Laravel Blade layout architecture ("Stitch") for fast rendering, lightweight rendering paths, and compatibility with traditional server-side pages.

---

## 1. Directory Structure

All files associated with the traditional Blade layouts and UI components are organized as follows:

```
typhoon/
├── resources/
│   └── views/
│       ├── layouts/
│       │   ├── stitch.blade.php  # Core parent layout (HTML skeleton, assets, nav, container)
│       │   ├── admin.blade.php   # Shared base layout for all administrative Blade routes
│       │   └── client.blade.php  # Shared base layout for all customer/client Blade routes
│       ├── components/
│       │   ├── button.blade.php  # Reusable Blade button component
│       │   └── card.blade.php    # Reusable Blade card component (used for security banners/notices)
│       ├── partials/
│       │   └── nav.blade.php     # Application global navigation bar with Auth condition checks
│       ├── admin/
│       │   └── dashboard.blade.php # Traditional admin dashboard view
│       └── client/
│           └── home.blade.php    # Traditional client home portal view
├── routes/
│   ├── web.php                   # Registers and includes traditional route groups
│   ├── admin.php                 # Administrative traditional routes (prefix: /admin)
│   └── client.php                # Client portal traditional routes (prefix: /client)
```

---

## 2. Layouts Hierarchy

Traditional pages inherit layout styles using standard Laravel Blade inheritance:

### A. Core Parent Layout (`layouts.stitch`)
File: `resources/views/layouts/stitch.blade.php`
- Defines the HTML skeleton, sets `<html class="dark">` for default premium dark mode, yields the page title and content, and pushes scripts/head stacks.
- Automatically includes the main navigation partial (`partials.nav`).

### B. Admin Layout (`layouts.admin`)
File: `resources/views/layouts/admin.blade.php`
- Extends `layouts.stitch`.
- Injects a standard security card notification and provides the standard structure for all admin pages.

### C. Client Layout (`layouts.client`)
File: `resources/views/layouts/client.blade.php`
- Extends `layouts.stitch`.
- Injects a session security warning indicator and establishes the layout flow for retail/corporate clients.

---

## 3. Reusable UI Components

The system provides easy-to-use component wrappers:

### Buttons
To render a standard themed primary button with transition animations:
```blade
@component('components.button')
    Underwrite Loan
@endcomponent
```

### Security Alert Cards
To display session status indicators:
```blade
@include('components.card')
```

---

## 4. Routing Configuration

Traditional routes are kept clean and separated from the main Inertia routes. They are registered via `routes/web.php` and loaded from individual module files:

- **Admin Module Routing:** Managed in `routes/admin.php` and prefixed under `/admin`.
- **Client Module Routing:** Managed in `routes/client.php` and prefixed under `/client`.

*Note: Access to administrative Blade views is protected using the `role:admin` middleware.*

---

## 5. Usage Example

To create a new traditional client page (e.g., `resources/views/client/settings.blade.php`):

```blade
@extends('layouts.client')

@section('title', 'Profile Settings')

@section('content')
    @parent {{-- Includes base header and security banner --}}

    <div class="mt-6">
        <h2 class="text-xl font-semibold mb-4 text-foreground">User Settings</h2>
        <div class="bg-card border border-border p-6 rounded-xl shadow-sm">
            <p class="text-sm text-muted-foreground mb-4">Manage your communication channels and security keys.</p>
            
            @component('components.button')
                Save Changes
            @endcomponent
        </div>
    </div>
@endsection
```
