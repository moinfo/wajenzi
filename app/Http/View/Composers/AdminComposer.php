<?php


namespace App\Http\View\Composers;


use App\Classes\Utility;
use App\Http\Controllers\Admin\MenusController;
use App\Models\Menu;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AdminComposer {
    protected $user;
    protected $user_notifications;
    protected $ui_notifications;
    protected $user_menu;
    protected $admin_menu;
    /**
     * Create a new profile composer.
     *
     * @param  Auth  $auth
     * @return void
     */
    public function __construct(Auth $auth) {
        $this->user = Auth::user();
        $this->user_notifications = $this->getNotifications();
        $this->user_menu = $this->getUserMenu($this->user);
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $data = [
            'page_title' => 'Financial Analysis',
            'user' => $this->user,
            'ui_notifications' => $this->getNotifications($view),
            'user_notifications' => $this->user_notifications,
            'user_menu' => $this->user_menu,
            'theme' => $this->getTheme()
        ];
        $view->with($data);
    }

//    private function getUserMenu($user) {
//        return Menu::getFullMenu();
//    }

    public function getUserMenu($user)
    {
        $menus = Menu::with('children')
            ->whereNull('parent_id')
            ->where('status', 'ACTIVE')
            ->orderBy('list_order')
            ->get();

        return $menus;
    }

    private function getNotifications() {
        if(!Auth::user()) { return []; }
        return Notification::forUser(Auth::user()->id);
    }

    private function getTheme() { // TODO this can be linked to user settings
        return [
            'sidebar' => ['inverse' => false, 'fixed' => true, 'mini' => false],
            'header' => ['inverse' => false, 'fixed' => false],
        ];
    }
}
