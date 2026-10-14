<?php
return [
    ['name' => 'user', 'display_name' => 'User Management', 'module' => 'User'],
    ['name' => 'user.users', 'display_name' => 'Users', 'module' => 'User', 'parent' => 'user'],
    ['name' => 'user.users.index', 'display_name' => 'View Users', 'module' => 'User', 'parent' => 'user.users'],
    ['name' => 'user.users.create', 'display_name' => 'Create Users', 'module' => 'User', 'parent' => 'user.users'],
    ['name' => 'user.users.edit', 'display_name' => 'Edit Users', 'module' => 'User', 'parent' => 'user.users'],
    ['name' => 'user.users.destroy', 'display_name' => 'Delete Users', 'module' => 'User', 'parent' => 'user.users'],
    ['name' => 'user.roles', 'display_name' => 'Roles', 'module' => 'User', 'parent' => 'user'],
    ['name' => 'user.roles.index', 'display_name' => 'View Roles', 'module' => 'User', 'parent' => 'user.roles'],
    ['name' => 'user.roles.create', 'display_name' => 'Create Roles', 'module' => 'User', 'parent' => 'user.roles'],
    ['name' => 'user.roles.edit', 'display_name' => 'Edit Roles', 'module' => 'User', 'parent' => 'user.roles'],
    ['name' => 'user.roles.destroy', 'display_name' => 'Delete Roles', 'module' => 'User', 'parent' => 'user.roles'],
    ['name' => 'user.reports', 'display_name' => 'Reports', 'module' => 'User', 'parent' => 'user'],
    ['name' => 'user.reports.index', 'display_name' => 'View Reports', 'module' => 'User', 'parent' => 'user.reports'],
    ['name' => 'user.reports.performance', 'display_name' => 'View Performance Reports', 'module' => 'User', 'parent' => 'user.reports'],
];