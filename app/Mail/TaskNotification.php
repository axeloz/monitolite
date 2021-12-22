<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TaskNotification extends Mailable
{
    use SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    protected $report;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Order  $order
     * @return void
     */
    public function __construct($report)
    {
        $this->report = $report;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
		return $this
			->subject('Monitolite Alert Report')
			->from(env('MAIL_FROM_ADDRESS', 'noreply@monitolite.fr'), env('MAIL_FROM_NAME', 'Monitolite'))
			->markdown('emails.notification')
			->with([
				'report' 	=> $this->report,
				'url'		=> env('APP_URL')
			])
		;
    }
}