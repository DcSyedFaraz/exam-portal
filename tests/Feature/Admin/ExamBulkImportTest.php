<?php

namespace Tests\Feature\Admin;

use App\Exports\ExamBulkTemplateExport;
use App\Models\Exam;
use App\Models\ExamImportBatch;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ExamBulkImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $user->assignRole('admin');

        return $user;
    }

    public function test_guest_cannot_access_bulk_import(): void
    {
        $this->get(route('admin.exams.bulk-import'))->assertRedirect(route('login'));
    }

    public function test_admin_can_download_templates(): void
    {
        $admin = $this->makeAdmin();

        $res = $this->actingAs($admin)
            ->get(route('admin.exams.bulk-import.template', ['with_examples' => 0]))
            ->assertOk();

        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $res->headers->get('content-type')
        );

        $this->actingAs($admin)
            ->get(route('admin.exams.bulk-import.template', ['with_examples' => 1]))
            ->assertOk();
    }

    public function test_import_example_workbook_creates_exams_and_questions(): void
    {
        Storage::fake(config('exam_import.disk'));
        $admin = $this->makeAdmin();

        Excel::store(new ExamBulkTemplateExport(true), 'fixtures/sample.xlsx', config('exam_import.disk'));

        $path = Storage::disk(config('exam_import.disk'))->path('fixtures/sample.xlsx');
        $file = new UploadedFile($path, 'sample.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $before = Exam::query()->count();

        $this->actingAs($admin)
            ->post(route('admin.exams.bulk-import.store'), ['file' => $file])
            ->assertRedirect();

        $this->assertSame($before + 1, Exam::query()->count());

        $batch = ExamImportBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame('completed', $batch->status);
        $this->assertNotEmpty($batch->summary_json['created_exam_ids'] ?? []);
    }

    public function test_empty_workbook_fails_import(): void
    {
        Storage::fake(config('exam_import.disk'));
        $admin = $this->makeAdmin();

        Excel::store(new ExamBulkTemplateExport(false), 'fixtures/empty.xlsx', config('exam_import.disk'));

        $path = Storage::disk(config('exam_import.disk'))->path('fixtures/empty.xlsx');
        $file = new UploadedFile($path, 'empty.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($admin)
            ->post(route('admin.exams.bulk-import.store'), ['file' => $file])
            ->assertRedirect();

        $batch = ExamImportBatch::query()->latest('id')->first();
        $this->assertNotNull($batch);
        $this->assertSame('failed', $batch->status);
        $this->assertNotNull($batch->error_report_path);
    }
}
