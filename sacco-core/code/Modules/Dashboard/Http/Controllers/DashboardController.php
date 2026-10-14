<?php

namespace Modules\Dashboard\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laracasts\Flash\Flash;
use Modules\Core\Sidebar\AdminSideBar;
use Arrilot\Widgets\Misc\NamespaceNotFoundException;
use Modules\Dashboard\Entities\Widget;
use Modules\Loan\Entities\Loan;
use Nwidart\Modules\Facades\Module;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:dashboard.edit'])->only(['create', 'store_widget', 'edit', 'update', 'update_widget_positions', 'reset_layout']);
        $this->middleware(['permission:dashboard.destroy'])->only(['destroy']);

    }

    protected function getAvailableWidgets()
    {
        $available_widgets = [];
        $modules = Module::allEnabled();
        foreach ($modules as $module) {
            if (file_exists(Module::getPath($module) . '/' . $module . '/Config/widgets.php')) {
                foreach (config($module->getLowerName() . ".widgets") as $key => $value) {
                    $available_widgets[$key] = $value;
                }
            }
        }

        return $this->filterWidgets($available_widgets, false);
    }

    /**
     * Drop widgets whose module namespace is not registered (e.g. disabled module).
     *
     * @param array|object $widgets
     * @param bool $asObject
     * @return array|object
     */
    protected function filterWidgets($widgets, $asObject = true)
    {
        if (empty($widgets)) {
            return $widgets;
        }

        $repo = app('arrilot.widget-namespaces');
        $filtered = [];

        foreach ((array) $widgets as $key => $widget) {
            $widget = (array) $widget;
            $class = $widget['class'] ?? '';

            if (!$class || strpos($class, '::') === false) {
                continue;
            }

            [$alias] = explode('::', $class, 2);

            try {
                $repo->getNamespace($alias);
                $filtered[$key] = $widget;
            } catch (NamespaceNotFoundException $e) {
                continue;
            }
        }

        if ($asObject) {
            return json_decode(json_encode($filtered));
        }

        return $filtered;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        if (Auth::user()->hasRole('client')) {
            return redirect('portal/dashboard');
        }
        $available_widgets = $this->getAvailableWidgets();
        $user_widgets = Widget::where('user_id', Auth::id())->first();
        if (!empty($user_widgets)) {
            $user_widgets = $this->filterWidgets(json_decode($user_widgets->widgets, true));
        } else {
            $user_widgets = $this->filterWidgets(config('dashboard.default_widgets', []));
        }
        \JavaScript::put([
            'user_widgets' => $user_widgets
        ]);
        return theme_view('dashboard::dashboard', compact('available_widgets', 'user_widgets'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return theme_view('dashboard::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store_widget(Request $request)
    {
        $available_widgets = $this->getAvailableWidgets();
        $widgets = [];
        $user_widgets = Widget::where('user_id', Auth::id())->first();
        if (!empty($user_widgets)) {
            $widgets = json_decode($user_widgets->widgets, true);
            if (!array_key_exists($request->widget_id, $widgets)) {
                $widgets[$request->widget_id] = $available_widgets[$request->widget_id];
            }
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();

        } else {
            $widgets[$request->widget_id] = $available_widgets[$request->widget_id];
            $user_widgets = new Widget();
            $user_widgets->user_id = Auth::id();
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();
        }
        Flash::success(trans_choice("core::general.successfully_saved", 1));
        return redirect()->back();
    }

    public function update_widget_positions(Request $request)
    {
        $available_widgets = $this->getAvailableWidgets();
        $widgets = [];
        $user_widgets = Widget::where('user_id', Auth::id())->first();
        if (!empty($user_widgets)) {
            $widgets = json_decode($user_widgets->widgets, true);
            foreach ($request->input('widgets') as $widget) {
                if (!array_key_exists($widget["id"], $widgets)) {
                    $available_widgets[$widget["id"]]["x"] = $widget["x"];
                    $available_widgets[$widget["id"]]["y"] = $widget["y"];
                    $available_widgets[$widget["id"]]["width"] = $widget["width"];
                    $available_widgets[$widget["id"]]["height"] = $widget["height"];
                    $widgets[$widget["id"]] = $available_widgets[$widget["id"]];
                }else{
                    $widgets[$widget["id"]]["x"] = $widget["x"];
                    $widgets[$widget["id"]]["y"] = $widget["y"];
                    $widgets[$widget["id"]]["width"] = $widget["width"];
                    $widgets[$widget["id"]]["height"] = $widget["height"];
                }
            }
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();

        }
        return response()->json(["success" => 1, "msg" => trans_choice("core::general.successfully_saved", 1)]);
    }

    public function reset_layout(Request $request)
    {
        $widgets = $this->filterWidgets(config('dashboard.default_widgets', []), false);
        $user_widgets = Widget::where('user_id', Auth::id())->first();
        if (!empty($user_widgets)) {
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();
        } else {
            $user_widgets = new Widget();
            $user_widgets->user_id = Auth::id();
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();
        }
        Flash::success(trans_choice("core::general.successfully_saved", 1));
        return redirect('dashboard');
    }

    public function remove_widget(Request $request)
    {


        $widgets = [];
        $user_widgets = Widget::where('user_id', Auth::id())->first();
        if (!empty($user_widgets)) {
            $widgets = json_decode($user_widgets->widgets, true);
            unset($widgets[$request->id]);
            $user_widgets->widgets = json_encode($widgets);
            $user_widgets->save();

        }
        return response()->json(["success" => 1, "msg" => trans_choice("core::general.successfully_saved", 1)]);
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return theme_view('dashboard::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return theme_view('dashboard::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
