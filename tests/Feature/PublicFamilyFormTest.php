<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\FamilySubmission;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFamilyFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_family_form_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);

        $response = $this->get('/form-warga');

        $response->assertOk();
        $response->assertSee('Form Kontribusi & Pendaftaran Keluarga', false);
        $response->assertSee('Nominal Rekomendasi');
        $response->assertSee('Rekening Tujuan Transfer');
        $response->assertSee('8800012345');
    }

    public function test_family_form_submission_is_stored_with_members_and_contributions(): void
    {
        Storage::fake('public');
        $this->seed(DatabaseSeeder::class);

        $event = Event::where('status', 'active')->firstOrFail();
        $competition = $event->competitions()->where('status', 'published')->firstOrFail();

        $response = $this->post('/form-warga', [
            'head_of_family_name' => 'Keluarga Test',
            'resident_block' => 'Z/09',
            'phone_number' => '081299999999',
            'email' => 'keluarga@test.com',
            'notes' => 'Mohon dicatat untuk dua anak.',
            'payment_method' => 'transfer',
            'payment_notes' => 'Transfer dari rekening keluarga.',
            'proof_file' => UploadedFile::fake()->image('bukti.jpg'),
            'contribution_iuran_amount' => '50.000',
            'contribution_iuran_note' => 'Iuran utama',
            'contribution_tambahan_amount' => '25.000',
            'contribution_tambahan_note' => 'Tambahan hadiah lomba',
            'contribution_donasi_amount' => 0,
            'contribution_sponsor_amount' => 0,
            'members' => [
                [
                    'name' => 'Ayah Test',
                    'relationship' => 'ayah',
                    'age' => 40,
                    'gender' => 'L',
                    'competition_id' => '',
                    'notes' => '',
                ],
                [
                    'name' => 'Anak Test',
                    'relationship' => 'anak',
                    'age' => 10,
                    'gender' => 'L',
                    'competition_id' => $competition->id,
                    'notes' => 'Mau ikut lomba.',
                ],
            ],
        ]);

        $response->assertRedirect('/form-warga');

        $submission = FamilySubmission::where('head_of_family_name', 'Keluarga Test')->first();

        $this->assertNotNull($submission);
        $this->assertSame('submitted', $submission->status);
        $this->assertEquals('75000.00', $submission->submitted_total);
        $this->assertCount(2, $submission->contributionItems);
        $this->assertCount(2, $submission->familyMembers);
        Storage::disk('public')->assertExists($submission->proof_file);
    }
}
