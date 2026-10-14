<?php

namespace Modules\User\Console;

use Illuminate\Console\Command;
use Modules\User\Entities\Role;
use Modules\User\Services\PermissionSyncService;

class RbacUpgradeCommand extends Command
{
    protected $signature = 'rbac:upgrade {--module= : Sync a single module only}';

    protected $description = 'Upgrade RBAC: sync permissions with hierarchy, portal permissions, and rebuild cache';

    public function handle(PermissionSyncService $syncService): int
    {
        $this->info('Upgrading RBAC...');

        if ($module = $this->option('module')) {
            $count = $syncService->syncModule($module);
            $this->info("Synced {$count} permission(s) from module: {$module}");
        } else {
            $count = $syncService->syncAll();
            $this->info("Synced {$count} permission change(s) across all modules.");
        }

        $portalPermissions = config('portal.permissions') ?? [];
        $portalNames = array_column($portalPermissions, 'name');
        if (!empty($portalNames)) {
            $syncService->assignPortalPermissionsToClient($portalNames);
            $this->info('Assigned portal permissions to client role.');
        }

        if (Role::where('name', 'client_view_only')->exists()) {
            $viewOnly = Role::findByName('client_view_only');
            $viewOnly->syncPermissions(
                Role::findByName('client')->permissions()->where('name', 'like', 'portal.%')->where('name', 'not like', '%.create')->where('name', 'not like', '%.store')->where('name', 'not like', '%.deposit')->where('name', 'not like', '%.withdraw')->where('name', 'not like', '%.transfer')->get()
            );
        }

        $syncService->clearCache();
        $this->info('RBAC upgrade complete.');

        return 0;
    }
}
