<?php

namespace App\Livewire;

use App\Models\UserAccountNotification;
use Livewire\Component;

class TopBarNotifications extends Component
{
    public $notifications;
    public $loading = false;

    public function mount()
    {
        // Fetch initial notifications on component mount
        $this->notifications = $this->getNotifications();
    }

    public function render()
    {
        return view('livewire.top-bar-notifications');
    }

    public function removeNotification($notificationId)
    {
        // Set loading to true before removing the notification
        $this->loading = true;
        // Remove the notification from the database
        UserAccountNotification::destroy($notificationId);

        // Refresh the Livewire component
        $this->notifications = $this->getNotifications();

        // Set loading back to false after removal is complete
        $this->loading = false;
    }

    public function clearAllNotifications()
    {
        // Set loading to true before clearing all notifications
        $this->loading = true;
        sleep(1);
        // Clear all notifications from the database
        UserAccountNotification::where('user_id', auth()->user()->user_id)->delete();

        // Refresh the Livewire component
        $this->notifications = $this->getNotifications();

        // Set loading back to false after clearing all notifications
        $this->loading = false;
    }


    private function getNotifications()
    {
        return UserAccountNotification::where('user_id', auth()->user()->user_id)
            ->latest()
            ->limit(10)
            ->get();
    }
}
