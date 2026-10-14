@foreach($nodes as $node)
    @php($permission = $node['permission'])
    <div class="permission-node mb-1" style="margin-left: {{ ($permission->parent_id ? 15 : 0) }}px">
        <div class="custom-control custom-checkbox">
            <input type="checkbox"
                   id="perm_{{ $permission->id }}"
                   name="{{ $inputName ?? 'permissions[]' }}"
                   value="{{ $permission->id }}"
                   class="custom-control-input perm-checkbox"
                   data-module="{{ $module }}"
                   @if(!empty($readonly)) disabled @endif
                   @if(in_array($permission->id, $selectedPermissions ?? [])) checked @endif>
            <label class="custom-control-label" for="perm_{{ $permission->id }}">
                {{ $permission->display_name }}
                <small class="text-muted">({{ $permission->name }})</small>
            </label>
        </div>
        @if(!empty($node['children']))
            <div class="pl-4">
                @include('user::partials.permission_tree_nodes', ['nodes' => $node['children'], 'module' => $module, 'readonly' => $readonly ?? false, 'inputName' => $inputName ?? 'permissions[]'])
            </div>
        @endif
    </div>
@endforeach
