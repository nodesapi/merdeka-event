<?php

namespace Tests\Feature;

use App\Models\CompetitionParticipant;
use App\Models\FamilySubmission;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminFamilySubmissionReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_verify_family_submission_and_create_records(): void
    {
        $this->seed(DatabaseSeeder::class);

        $competition = \App\Models\Competition::where('status', 'published')->firstOrFail();

        $submission = FamilySubmission::create([
            'event_id' => $competition->event_id,
            'reference_code' => 'REG-TEST-0001',
            'head_of_family_name' => 'Keluarga Review',
            'resident_block' => 'X/01',
            'phone_number' => '081288888888',
            'recommended_amount' => 50000,
            'submitted_total' => 125000,
            'payment_method' => 'transfer',
            'status' => 'submitted',
        ]);

        $item = $submission->contributionItems()->create([
            'type' => 'iuran',
            'amount' => 125000,
            'label' => 'Iuran Warga',
        ]);

        $member = $submission->familyMembers()->create([
            'name' => 'Anak Review',
            'relationship' => 'anak',
            'age' => 11,
            'gender' => 'P',
            'competition_id' => $competition->id,
        ]);

        $admin = User::where('username', 'superadmin')->firstOrFail();

        Livewire::actingAs($admin)
            ->test('family-submission-manager')
            ->call('selectSubmission', $submission->id)
            ->set('reviewNotes', 'Data lengkap dan valid.')
            ->call('verifySubmission')
            ->assertSee('berhasil diverifikasi');

        $submission->refresh();
        $item->refresh();
        $member->refresh();

        $this->assertSame('verified', $submission->status);
        $this->assertNotNull($submission->verified_at);
        $this->assertDatabaseHas((new Transaction())->getTable(), [
            'contribution_item_id' => $item->id,
            'type' => 'income',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas((new CompetitionParticipant())->getTable(), [
            'family_member_id' => $member->id,
            'competition_id' => $competition->id,
            'name' => 'Anak Review',
        ]);
    }
}
