<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ClassroomBatchTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $role = 'owner'): array
    {
        $t = $this->createTenant();
        $u = $this->createUser($t, $role);
        $this->actingAs($u, 'web');
        $room = Classroom::create(['name' => 'Sample room', 'capacity' => 20]);
        $a = Child::create(['first_name' => 'Alpha', 'last_name' => 'Sample', 'status' => 'active', 'classroom_id' => $room->id, 'health_notes' => 'PRIVATE HEALTH']);
        $b = Child::create(['first_name' => 'Beta', 'last_name' => 'Sample', 'status' => 'active', 'classroom_id' => $room->id]);
        $data = ['classroom_id' => $room->id, 'kind' => 'care', 'date' => $t->today(), 'child_ids' => [$a->id, $b->id], 'fields' => ['meals' => 'Shared meal', 'naps' => '45 minutes']];

        return compact('t', 'u', 'room', 'a', 'b', 'data');
    }

    public function test_fill_missing_preserves_existing_values_unpublishes_changed_logs_and_is_idempotent(): void
    {
        extract($this->fixture());
        $old = DailyLog::create(['child_id' => $a->id, 'date' => $t->today(), 'meals' => 'Existing meal', 'notes' => 'Keep these notes', 'family_visible' => true, 'created_by' => $u->id]);
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->assertJsonPath('changed_count', 2)->json();
        $url = '/classroom-batches/'.$p['id'].'/apply';
        $this->assertDatabaseCount('daily_logs', 1);
        $this->postJson($url, [])->assertUnprocessable();
        $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('changed_count', 2);
        $this->postJson($url, ['confirmed' => true])->assertOk()->assertJsonPath('changed_count', 2);
        $this->assertDatabaseCount('daily_logs', 2);
        $this->assertEquals('Existing meal', $old->fresh()->meals);
        $this->assertEquals('Keep these notes', $old->fresh()->notes);
        $this->assertEquals('45 minutes', $old->fresh()->naps);
        $this->assertFalse((bool) $old->fresh()->family_visible);
        $this->assertDatabaseHas('daily_logs', ['child_id' => $b->id, 'meals' => 'Shared meal', 'family_visible' => false]);
    }

    public function test_stale_preview_never_overwrites_a_new_individual_edit(): void
    {
        extract($this->fixture());
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->json();
        DailyLog::create(['child_id' => $b->id, 'date' => $t->today(), 'notes' => 'A newer individual note', 'created_by' => $u->id]);
        $this->postJson('/classroom-batches/'.$p['id'].'/apply', ['confirmed' => true])->assertStatus(409);
        $this->assertDatabaseCount('daily_logs', 1);
        $this->assertDatabaseHas('daily_logs', ['notes' => 'A newer individual note', 'meals' => '']);
    }

    public function test_teacher_can_fill_missing_but_cannot_bulk_replace_or_see_health_notes(): void
    {
        extract($this->fixture('teacher'));
        $this->get('/classroom-batches?classroom_id='.$room->id)->assertOk()->assertDontSee('PRIVATE HEALTH');
        $this->postJson('/classroom-batches/preview', $data + ['replace_existing' => true])->assertForbidden();
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->json();
        $this->postJson('/classroom-batches/'.$p['id'].'/apply', ['confirmed' => true])->assertOk();
    }

    public function test_manager_can_replace_only_explicitly_entered_fields(): void
    {
        extract($this->fixture('manager'));
        $old = DailyLog::create(['child_id' => $a->id, 'date' => $t->today(), 'meals' => 'Old meal', 'notes' => 'Keep notes', 'created_by' => $u->id]);
        $data['fields'] = ['meals' => 'Reviewed replacement', 'notes' => ''];
        $data['replace_existing'] = true;
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->json();
        $this->postJson('/classroom-batches/'.$p['id'].'/apply', ['confirmed' => true])->assertOk();
        $this->assertEquals('Reviewed replacement', $old->fresh()->meals);
        $this->assertEquals('Keep notes', $old->fresh()->notes);
    }

    public function test_preview_is_actor_scoped_expires_and_stops_after_child_moves(): void
    {
        extract($this->fixture());
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->json();
        $url = '/classroom-batches/'.$p['id'].'/apply';
        $this->actingAs($this->createUser($t), 'web');
        $this->postJson($url, ['confirmed' => true])->assertNotFound();
        $this->actingAs($u, 'web');
        $a->update(['classroom_id' => null]);
        $this->postJson($url, ['confirmed' => true])->assertUnprocessable();
        $a->update(['classroom_id' => $room->id]);
        $this->travel(601)->seconds();
        $this->postJson($url, ['confirmed' => true])->assertStatus(410);
        $this->travelBack();
        $this->assertDatabaseCount('daily_logs', 0);
    }

    public function test_cross_tenant_children_and_duplicate_selection_are_rejected(): void
    {
        extract($this->fixture());
        $other = $this->createTenant('Another tenant');
        $this->actingAs($this->createUser($other), 'web');
        $otherRoom = Classroom::create(['name' => 'Private room', 'capacity' => 20]);
        $private = Child::create(['first_name' => 'PRIVATE', 'last_name' => 'Sample', 'classroom_id' => $otherRoom->id, 'status' => 'active']);
        $this->actingAs($u, 'web');
        $this->get('/classroom-batches?classroom_id='.$otherRoom->id)->assertNotFound();
        $data['child_ids'] = [$a->id, $private->id];
        $this->postJson('/classroom-batches/preview', $data)->assertUnprocessable();
        $data['child_ids'] = [$a->id, $a->id];
        $this->postJson('/classroom-batches/preview', $data)->assertUnprocessable();
        $this->assertDatabaseCount('classroom_batches', 0);
    }

    public function test_bulk_arrival_is_today_only_skips_already_present_and_never_checks_out(): void
    {
        extract($this->fixture());
        Attendance::create(['child_id' => $a->id, 'date' => $t->today(), 'check_in_at' => now()->subHour(), 'checked_in_by' => $u->id]);
        $data['kind'] = 'checkin';
        $data['date'] = now($t->timezone)->subDay()->toDateString();
        $this->postJson('/classroom-batches/preview', $data)->assertUnprocessable();
        $data['date'] = $t->today();
        $p = $this->postJson('/classroom-batches/preview', $data)->assertOk()->assertJsonPath('changed_count', 1)->assertJsonPath('unchanged_count', 1)->json();
        $this->postJson('/classroom-batches/'.$p['id'].'/apply', ['confirmed' => true])->assertOk();
        $this->assertDatabaseCount('attendance', 2);
        $this->assertEquals(0, DB::table('attendance')->whereNotNull('check_out_at')->count());
        $this->assertDatabaseCount('daily_logs', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_checked_out_child_rejects_the_whole_arrival_batch(): void
    {
        extract($this->fixture());
        Attendance::create(['child_id' => $a->id, 'date' => $t->today(), 'check_in_at' => now()->subHours(2), 'check_out_at' => now()->subHour(), 'checked_in_by' => $u->id, 'checked_out_by' => $u->id]);
        $data['kind'] = 'checkin';
        $this->postJson('/classroom-batches/preview', $data)->assertUnprocessable();
        $this->assertDatabaseCount('attendance', 1);
    }
}
