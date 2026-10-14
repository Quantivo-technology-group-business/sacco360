@extends('core::layouts.master')
@section('title')
    {{trans_choice('dashboard::general.dashboard',1)}}
@endsection
@section('styles')
<style>
/* ── Dashboard page styles ───────────────────────────── */
.dash-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    padding: 1rem 1.25rem 0.875rem;
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 2px 8px rgba(12,35,64,.06);
}

.dash-header__left { display: flex; align-items: center; gap: 0.75rem; }

.dash-header__title {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--sacco-primary, #0c2340);
    margin: 0;
    letter-spacing: -0.02em;
}

.dash-header__subtitle {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
    margin-top: 1px;
}

.dash-header__icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--sacco-primary, #0c2340), var(--sacco-primary-dark, #081829));
    color: var(--sacco-accent, #c5a572);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}

.dash-header__actions { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.dash-menu-search {
    position: relative;
    min-width: 220px;
}

.dash-menu-search__input {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.4rem 2.25rem 0.4rem 0.85rem;
    font-size: 0.82rem;
    color: #334155;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
    height: 36px;
}

.dash-menu-search__input:focus {
    outline: none;
    border-color: var(--sacco-primary, #0c2340);
    box-shadow: 0 0 0 3px rgba(12,35,64,.10);
    background: #fff;
}

.dash-menu-search__icon {
    position: absolute;
    right: 0.7rem;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 0.75rem;
    pointer-events: none;
}

.dash-menu-search__dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    box-shadow: 0 8px 32px rgba(12,35,64,.13);
    max-height: 300px;
    overflow-y: auto;
    z-index: 1050;
    display: none;
}

.dash-menu-search__dropdown.show { display: block; }

.dash-menu-search__item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 0.9rem;
    font-size: 0.82rem;
    color: #334155;
    font-weight: 500;
    text-decoration: none;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.dash-menu-search__item:last-child { border-bottom: none; }
.dash-menu-search__item:hover { background: #f8fafc; color: var(--sacco-primary, #0c2340); text-decoration: none; }
.dash-menu-search__item i { width: 16px; text-align: center; color: var(--sacco-primary, #0c2340); opacity: 0.7; }

.dash-menu-search__empty {
    padding: 1rem;
    text-align: center;
    font-size: 0.8rem;
    color: #94a3b8;
}

/* ── Info-box improvements ───────────────────────────── */
.info-box {
    border-radius: 12px !important;
    box-shadow: 0 2px 10px rgba(12,35,64,.08) !important;
    border: 1px solid #e2e8f0;
    min-height: 72px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
    background: #fff;
}

.info-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 28px rgba(12,35,64,.13) !important;
}

.info-box-icon {
    width: 64px !important;
    min-width: 64px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem !important;
    border-radius: 0;
    flex-shrink: 0;
}

.info-box-content {
    padding: 0.85rem 1rem !important;
    flex: 1;
    min-width: 0;
}

.info-box-text {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.07em !important;
    color: #64748b !important;
    display: block;
    margin-bottom: 0.2rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.info-box-number {
    font-size: 1.3rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.025em !important;
    color: #1e293b !important;
    line-height: 1.2;
    display: block;
}

/* icon colors */
.info-box-icon.bg-info    { background: linear-gradient(135deg, #0e7490, #0891b2) !important; }
.info-box-icon.bg-success { background: linear-gradient(135deg, #15803d, #16a34a) !important; }
.info-box-icon.bg-warning { background: linear-gradient(135deg, #b45309, #d97706) !important; }
.info-box-icon.bg-danger  { background: linear-gradient(135deg, #b91c1c, #dc2626) !important; }
.info-box-icon.bg-primary { background: linear-gradient(135deg, var(--sacco-primary-dark, #081829), var(--sacco-primary, #0c2340)) !important; }
.info-box-icon.bg-teal    { background: linear-gradient(135deg, #0f766e, #14b8a6) !important; }
.info-box-icon.bg-purple  { background: linear-gradient(135deg, #6d28d9, #7c3aed) !important; }
.info-box-icon.bg-indigo  { background: linear-gradient(135deg, #3730a3, #4338ca) !important; }
.info-box-icon.bg-orange  { background: linear-gradient(135deg, #c2410c, #ea580c) !important; }
.info-box-icon.bg-olive   { background: linear-gradient(135deg, #4d7c0f, #65a30d) !important; }
.info-box-icon.bg-navy    { background: linear-gradient(135deg, #0c2340, #1a3a5c) !important; }
.info-box-icon.bg-yellow  { background: linear-gradient(135deg, #a16207, #ca8a04) !important; }
.info-box-icon.bg-red     { background: linear-gradient(135deg, #9f1239, #be123c) !important; }
.info-box-icon.bg-green   { background: linear-gradient(135deg, #15803d, #16a34a) !important; }
.info-box-icon.bg-secondary { background: linear-gradient(135deg, #475569, #64748b) !important; }

/* sacco-primary icon */
.info-box-icon.bg-info,
.info-box-icon[class*="bg-"] {
    color: rgba(255,255,255,.95) !important;
}

/* ── Small-box improvements ──────────────────────────── */
.small-box {
    border-radius: 12px !important;
    box-shadow: 0 4px 16px rgba(12,35,64,.12) !important;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
    margin-bottom: 0.75rem;
}

.small-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(12,35,64,.16) !important;
}

.small-box .inner h3 {
    font-size: 1.7rem !important;
    font-weight: 800 !important;
    letter-spacing: -0.03em;
}

.small-box .inner p {
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    opacity: 0.88;
}

.small-box .icon {
    font-size: 3rem !important;
    opacity: 0.18;
}

.small-box > .small-box-footer {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.45rem 1rem;
}

/* ── Grid stack ──────────────────────────────────────── */
.grid-stack {
    background: transparent;
    min-height: 600px;
}

.grid-stack-item-content {
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(12,35,64,.07);
    background: #fff;
    border: 1px solid #e2e8f0;
    transition: box-shadow 0.2s;
}

.grid-stack-item-content:hover { box-shadow: 0 6px 24px rgba(12,35,64,.11); }

.grid-stack-item-content .card {
    margin-bottom: 0;
    height: 100%;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

.grid-stack-item-content .card-header {
    background: #fff;
    border-bottom: 1px solid #e2e8f0;
    padding: 0.85rem 1.1rem;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b;
}

.grid-stack-item-content .card-title {
    font-size: 0.78rem !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: #64748b !important;
    margin: 0;
}

.grid-stack-item-content .small-box,
.grid-stack-item-content .info-box {
    min-height: 78px;
    margin-bottom: 0;
}

/* ── Chart widget summary stats ──────────────────────── */
.chart-stat-row { border-bottom: 1px solid #f1f5f9; padding: 0.6rem 1rem; }
.chart-stat-label { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; }
.chart-stat-value { font-size: 1.05rem; font-weight: 800; letter-spacing: -0.02em; }

/* ── Trash/remove button ─────────────────────────────── */
.trash-item {
    position: absolute;
    bottom: 0;
    right: 16px;
    display: none;
    z-index: 5;
}

.grid-stack-item-content:hover .trash-item {
    display: block;
    position: absolute;
    bottom: 0;
    right: 16px;
}

.trash-item .btn {
    font-size: 0.72rem;
    padding: 0.2rem 0.55rem;
    border-radius: 8px 8px 0 0;
    opacity: 0.75;
}

.trash-item .btn:hover { opacity: 1; }

/* ── Dashboard toolbar ───────────────────────────────── */
.dashboard-toolbar { padding: 0 0.25rem 1rem; }
.dashboard-toolbar .btn { margin-left: 6px; font-size: 0.8rem; }

/* ── Highcharts polish ───────────────────────────────── */
.highcharts-container {
    font-family: 'Inter', 'Source Sans Pro', sans-serif !important;
}
</style>
@stop
@section('content')
    {{-- Page header with search --}}
    <div class="dash-header">
        <div class="dash-header__left">
            <div class="dash-header__icon">
                <i class="fas fa-th-large"></i>
            </div>
            <div>
                <h1 class="dash-header__title">{{trans_choice('dashboard::general.dashboard',1)}}</h1>
                <p class="dash-header__subtitle">{{ now()->format('l, d F Y') }}</p>
            </div>
        </div>
        <div class="dash-header__actions">
            {{-- Menu search --}}
            <div class="dash-menu-search" id="dashMenuSearch">
                <input type="text"
                       class="dash-menu-search__input"
                       id="dashMenuSearchInput"
                       placeholder="{{ trans('core::general.search_menu', []) ?? 'Search Menu' }}..."
                       autocomplete="off"
                       aria-label="Search menu">
                <i class="fas fa-search dash-menu-search__icon"></i>
                <div class="dash-menu-search__dropdown" id="dashMenuSearchDropdown"></div>
            </div>

            {{-- Add widget --}}
            <button data-toggle="modal" data-target="#add_widget" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i>{{trans_choice('core::general.add',1)}} {{trans_choice('dashboard::general.widget',1)}}
            </button>

            {{-- Reset layout --}}
            <form method="post" action="{{ url('dashboard/reset_layout') }}" class="d-inline"
                  onsubmit="return confirm('{{ __('Reset dashboard to the full detailed layout? This replaces your current widget arrangement.') }}')">
                {{ csrf_field() }}
                <button type="submit" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-sync mr-1"></i>{{ __('Reset') }}
                </button>
            </form>
        </div>
    </div>

    {{-- Add widget modal --}}
    <div class="modal fade" id="add_widget" tabindex="-1" role="dialog" aria-labelledby="addWidgetModalLabel" aria-modal="true">
        <div class="modal-dialog" role="document">
            <form method="post" action="{{ url('dashboard/store_widget') }}" enctype="multipart/form-data">
                {{csrf_field()}}
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addWidgetModalLabel">
                            <i class="fas fa-plus-circle mr-2" style="color: var(--sacco-accent,#c5a572)"></i>
                            {{trans_choice('core::general.add',1)}} {{trans_choice('dashboard::general.widget',1)}}
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('Close') }}">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-0">
                            <label for="widget_id" class="control-label">{{trans_choice('dashboard::general.widget',1)}}</label>
                            <select class="form-control" name="widget_id" id="widget_id" required>
                                <option value="">— {{ trans_choice('dashboard::general.widget',1) }} —</option>
                                @foreach($available_widgets as $key=>$value)
                                    <option value="{{$key}}">{{$value["name"]}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss="modal">
                            {{trans_choice('core::general.close',1)}}
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus mr-1"></i>{{trans_choice('core::general.save',1)}}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Main content -->
    <section class="content" style="padding-top: 0;">
        <div id="app">
            <div class="row">
                <div class="col-md-12">
                    <div class="grid-stack">
                        @foreach($user_widgets as $user_widget)
                            @widget($user_widget->class, ['x' =>
                            $user_widget->x,"y"=>$user_widget->y,"width"=>$user_widget->width,"height"=>$user_widget->height])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.0.6/highcharts.js" charset="utf-8"></script>
    <script>
    /* ── GridStack ── */
    var grid = GridStack.init({
        cellHeight: 90,
        margin: 8,
        animate: true,
        float: false
    });

    grid.on('change', function (event, items) {
        var data = [];
        if (items) {
            items.forEach(function (item, index) {
                data[index] = { id: item.id, x: item.x, y: item.y, width: item.w, height: item.h };
            });
        }
        axios.post('{{url('dashboard/update_widget_positions')}}', {
            widgets: data,
            _token: '{{csrf_token()}}'
        }).catch(function () {
            toastr.warning("{{trans_choice("dashboard::general.failed_rearrange", 1)}}");
        });
    });

    /* ── Menu search ── */
    (function () {
        var input    = document.getElementById('dashMenuSearchInput');
        var dropdown = document.getElementById('dashMenuSearchDropdown');
        if (!input || !dropdown) return;

        /* Build menu index from sidebar links */
        var menuItems = [];
        document.querySelectorAll('.main-sidebar .nav-link, .main-sidebar .nav-treeview .nav-link').forEach(function (el) {
            var text = el.querySelector('p') ? el.querySelector('p').textContent.trim() : el.textContent.trim();
            var icon = el.querySelector('i') ? el.querySelector('i').className : 'fas fa-circle';
            var href = el.getAttribute('href');
            if (text && href && href !== '#') {
                menuItems.push({ text: text, icon: icon, href: href });
            }
        });

        function render(items) {
            if (!items.length) {
                dropdown.innerHTML = '<div class="dash-menu-search__empty"><i class="fas fa-search" style="display:block;margin-bottom:.35rem;font-size:1.1rem;opacity:.4"></i>No results found</div>';
            } else {
                dropdown.innerHTML = items.slice(0, 10).map(function (item) {
                    return '<a href="' + item.href + '" class="dash-menu-search__item"><i class="' + item.icon + '"></i>' + item.text + '</a>';
                }).join('');
            }
            dropdown.classList.add('show');
        }

        input.addEventListener('input', function () {
            var q = this.value.trim().toLowerCase();
            if (!q) { dropdown.classList.remove('show'); return; }
            var matched = menuItems.filter(function (m) { return m.text.toLowerCase().indexOf(q) !== -1; });
            render(matched);
        });

        input.addEventListener('focus', function () {
            if (this.value.trim().length > 0) dropdown.classList.add('show');
        });

        document.addEventListener('click', function (e) {
            if (!document.getElementById('dashMenuSearch').contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { dropdown.classList.remove('show'); this.value = ''; }
        });
    })();
    </script>
@endsection
