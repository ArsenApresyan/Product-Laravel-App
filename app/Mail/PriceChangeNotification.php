<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use App\Models\Product;

class PriceChangeNotification extends Mailable
{
    public $product;
    public $oldPrice;
    public $newPrice;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($product, $oldPrice, $newPrice)
    {
        $this->product = $product;
        $this->oldPrice = $oldPrice;
        $this->newPrice = $newPrice;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Product Price Change Notification')
                    ->view('emails.price-change');
    }
}
