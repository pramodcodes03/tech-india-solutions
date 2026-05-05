<?php

namespace App\Mail;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Base for every transactional email in the app.
 *
 * - Carries the per-business context (logo, name, address, currency).
 * - Sets the FROM address from MAIL_FROM_ADDRESS / MAIL_FROM_NAME with the
 *   business name appended for clarity.
 * - All children should extend this and implement build() / envelope().
 */
abstract class BaseBusinessMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Business $business) {}

    /**
     * Default sender. The address MUST match MAIL_FROM_ADDRESS (the
     * authenticated SMTP mailbox) — Microsoft 365 / Outlook reject any
     * "From" that differs from the authenticated user with 554 SendAsDenied.
     * The display name still carries the business identity, so recipients
     * see e.g. "Apparel & Leather Technics <erp@techindia.biz>".
     */
    protected function defaultFrom(): array
    {
        return [
            'address' => config('mail.from.address'),
            'name' => $this->business->name ?: config('mail.from.name'),
        ];
    }

    /**
     * Reply-To target. We point this at the business's own contact email
     * so customer replies reach the right team, not the shared ERP mailbox.
     */
    protected function defaultReplyTo(): ?array
    {
        if (empty($this->business->email)) {
            return null;
        }

        return [
            'address' => $this->business->email,
            'name' => $this->business->name,
        ];
    }
}
