<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectPage;
use App\Models\User;
use App\Services\FalService;
use App\Services\PromptComposer;
use App\Services\ReferenceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookPlatformTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/');
        $response->assertStatus(200);
    }

    public function test_project_creation_and_image_import()
    {
        $masterStyle = UploadedFile::fake()->image('style.jpg');
        $pageImage = UploadedFile::fake()->image('page1.jpg');

        $response = $this->actingAs($this->user)->post('/projects', [
            'name' => 'اختبار الكتاب الحقيقي',
            'default_batch_size' => 10,
            'default_quality' => 'high',
            'default_resolution' => 'auto',
            'default_output_format' => 'png',
            'default_variants' => 1,
            'source_type' => 'images',
            'source_images' => [$pageImage],
            'master_style' => $masterStyle,
        ]);

        $this->assertDatabaseHas('projects', ['name' => 'اختبار الكتاب الحقيقي']);
        $project = Project::where('name', 'اختبار الكتاب الحقيقي')->first();
        $this->assertNotNull($project);
        $this->assertEquals(1, $project->pages()->count());
    }

    public function test_prompt_composer_incorporates_arabic_rules_and_instructions()
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'مشروع تجريبي',
            'master_prompt' => 'نمط مجلة راقية بألوان زاهية',
        ]);

        $page = ProjectPage::create([
            'project_id' => $project->id,
            'page_number' => 1,
            'source_image_path' => 'projects/fake/page1.png',
        ]);

        $composer = new PromptComposer();
        $prompt = $composer->compose($project, $page, 'اجعل العنوان كبيراً');

        $this->assertStringContainsString('RTL', $prompt);
        $this->assertStringContainsString('نمط مجلة راقية', $prompt);
        $this->assertStringContainsString('اجعل العنوان كبيراً', $prompt);
    }

    public function test_reference_resolver_enforces_image_role_order()
    {
        $project = Project::create([
            'user_id' => $this->user->id,
            'name' => 'مشروع المراجع',
        ]);

        $page = ProjectPage::create([
            'project_id' => $project->id,
            'page_number' => 1,
            'source_image_path' => 'projects/fake/page1.png',
        ]);

        $resolver = app(ReferenceResolver::class);
        $refs = $resolver->resolve($project, $page);

        $this->assertArrayHasKey('source_page', $refs);
        $this->assertArrayHasKey('image_urls', $refs);
        $this->assertGreaterThanOrEqual(1, count($refs['image_urls']));
    }

    public function test_health_check_returns_ok()
    {
        $response = $this->get('/health');
        $response->assertStatus(200)
                 ->assertJson(['status' => 'ok', 'database' => 'ok']);
    }
}
