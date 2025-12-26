<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Mail\ContactSubmitted;

class ContactFormTest extends TestCase
{
    public function test_contact_form_sends_email()
    {
        Mail::fake();

        $response = $this->post('/contact/send', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'message' => 'Hello from PHPUnit',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertSent(ContactSubmitted::class, function ($mail) {
            return $mail->hasTo(config('juris.emails.contact'));
        });
    }
}
