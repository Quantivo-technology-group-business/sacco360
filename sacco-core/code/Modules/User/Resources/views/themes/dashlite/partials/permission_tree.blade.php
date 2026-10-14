@php($inputName = $inputName ?? 'permissions[]')
@php($tree = $permissionTree ?? \Modules\User\Entities\Permission::treeByModule())
@foreach($tree as $module => $nodes)
    <div class="permission-module mb-3">
        <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" id="module_{{ $module }}" class="custom-control-input module-toggle"
                   data-module="{{ $module }}">
            <label class="custom-control-label" for="module_{{ $module }}">
                <span class="title h5">{{ $module }}</span>
            </label>
        </div>
        <div class="pl-3">
            @include('user::partials.permission_tree_nodes', ['nodes' => $nodes, 'module' => $module, 'readonly' => $readonly ?? false, 'inputName' => $inputName])
        </div>
    </div>
@endforeach
