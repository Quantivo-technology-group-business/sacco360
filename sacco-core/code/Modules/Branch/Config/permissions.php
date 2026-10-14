<?php
return [
    ['name' => 'branch', 'display_name' => 'Branch Management', 'module' => 'Branch'],
    ['name' => 'branch.branches', 'display_name' => 'Branches', 'module' => 'Branch', 'parent' => 'branch'],
    ['name' => 'branch.branches.index', 'display_name' => 'View Branches', 'module' => 'Branch', 'parent' => 'branch.branches'],
    ['name' => 'branch.branches.create', 'display_name' => 'Create Branches', 'module' => 'Branch', 'parent' => 'branch.branches'],
    ['name' => 'branch.branches.edit', 'display_name' => 'Edit Branches', 'module' => 'Branch', 'parent' => 'branch.branches'],
    ['name' => 'branch.branches.destroy', 'display_name' => 'Delete Branches', 'module' => 'Branch', 'parent' => 'branch.branches'],
    ['name' => 'branch.branches.assign_user', 'display_name' => 'Assign Users', 'module' => 'Branch', 'parent' => 'branch.branches'],
    ['name' => 'branch.branches.all_branches', 'display_name' => 'Access All Branches', 'module' => 'Branch', 'parent' => 'branch.branches'],
];