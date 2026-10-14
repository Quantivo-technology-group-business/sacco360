# RBAC Upgrade Guide

Ultimate Loan Manager uses a full Role-Based Access Control (RBAC) system built on Spatie Laravel Permission with ULM extensions.

## Features

- **Permission hierarchy** — parent permissions imply child permissions (`parent_id` tree)
- **Role inheritance** — roles can inherit from a parent role (`parent_role_id`)
- **Direct user permissions** — grant extra permissions on individual users
- **Branch scoping** — staff see data for assigned branches unless they have `branch.branches.all_branches`
- **Own-record scoping** — `*_own` permissions limit records to the user's `loan_officer_id`
- **Laravel Policies** — resource-level checks on Client, Loan, Savings, Share, User
- **Portal RBAC** — granular `portal.*` permissions for member portal features

## Upgrading Existing Installations

Run the upgrade command after deploying:

```bash
php artisan rbac:upgrade
```

This will:

1. Sync all module permissions (including hierarchy)
2. Assign portal permissions to the `client` role
3. Rebuild the permission cache

To sync a single module:

```bash
php artisan rbac:upgrade --module=Sacco
```

Run migrations first if upgrading from a version before RBAC:

```bash
php artisan module:migrate User
```

## Managing Roles

Navigate to **Users → Manage Roles**. When creating or editing a role:

- Select a **parent role** to inherit its permissions
- Use the **permission tree** to assign direct role permissions

## Direct User Permissions

Edit any staff user and scroll to **Direct Permissions** to grant permissions beyond their role(s).

## Portal Sub-Roles

| Role | Purpose |
|------|---------|
| `client` | Full portal access (default for members) |
| `client_view_only` | Read-only portal access |
| `client_transact` | Inherits view-only + transaction permissions |

## Admin Bypass

Users with the `admin` role bypass branch and own-record scoping but still require permissions for route access.

## Permission Naming

Permissions follow `{module}.{resource}.{action}` with optional `*_own` variants for scoped access.

Examples:

- `client.clients.index` — view all clients (within branch scope)
- `client.clients.index_own` — view only assigned clients
- `portal.savings.deposit` — portal savings deposit

## Developer Notes

- Use `AuthorizationService` for scoped checks: `app(AuthorizationService::class)->can($user, 'client.clients.edit', $client)`
- Use `AuthorizesWithScope` trait in controllers for `$this->applyAuthorizationScopes($query, 'client.clients.index')`
- Register new permissions in `Modules/{Module}/Config/permissions.php` with optional `parent` key
