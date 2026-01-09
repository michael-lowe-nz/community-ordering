<?php

namespace App\Notifications;

use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ScrapingFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The error message.
     *
     * @var string
     */
    protected $errorMessage;

    /**
     * The restaurant that failed scraping.
     *
     * @var \App\Models\Restaurant|null
     */
    protected $restaurant;

    /**
     * The scraping frequency.
     *
     * @var string|null
     */
    protected $frequency;

    /**
     * Create a new notification instance.
     *
     * @param string $errorMessage
     * @param \App\Models\Restaurant|null $restaurant
     * @param string|null $frequency
     * @return void
     */
    public function __construct(string $errorMessage, ?Restaurant $restaurant = null, ?string $frequency = null)
    {
        $this->errorMessage = $errorMessage;
        $this->restaurant = $restaurant;
        $this->frequency = $frequency;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $message = (new MailMessage)
            ->subject('Menu Scraping Failure Alert')
            ->line('A menu scraping operation has failed.');

        if ($this->restaurant) {
            $message->line('Restaurant: ' . $this->restaurant->name)
                   ->line('Menu URL: ' . $this->restaurant->menu_url);
        } else {
            $message->line('Scheduled scraping job for frequency: ' . ($this->frequency ?? 'all'));
        }

        $message->line('Error: ' . $this->errorMessage)
               ->action('View Scraping Dashboard', url(route('admin.menu-scraping.dashboard')))
               ->line('Please check the scraping configuration and try again.');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $data = [
            'type' => 'scraping_failure',
            'error_message' => $this->errorMessage,
            'frequency' => $this->frequency,
        ];

        if ($this->restaurant) {
            $data['restaurant_id'] = $this->restaurant->id;
            $data['restaurant_name'] = $this->restaurant->name;
            $data['restaurant_menu_url'] = $this->restaurant->menu_url;
        }

        return $data;
    }
}