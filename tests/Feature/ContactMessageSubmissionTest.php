<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessageSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_stores_feedback(): void
    {
        $this->post(route('contact.submit'), [
            'name' => 'Pengunjung Uji',
            'email' => 'pengunjung@example.test',
            'whatsapp' => '081234567890',
            'category' => 'feedback',
            'subject' => 'Pelayanan',
            'message' => 'Masukan pengujian saja.',
        ])->assertRedirect(route('contact'))->assertSessionHas('success');

        $this->assertDatabaseHas('saran_masukan', [
            'email' => 'pengunjung@example.test',
            'category' => 'feedback',
            'status' => 'baru',
            'message' => 'Masukan pengujian saja.',
        ]);
    }

    public function test_invalid_contact_form_is_rejected(): void
    {
        $this->from(route('contact'))->post(route('contact.submit'), [
            'name' => 'Uji',
            'email' => 'invalid',
            'category' => 'invalid',
            'subject' => 'S',
            'message' => '',
        ])->assertRedirect(route('contact'))->assertSessionHasErrors(['email', 'category', 'message']);

        $this->assertDatabaseCount('saran_masukan', 0);
    }
}