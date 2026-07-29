<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
class PdCreateSmokeTest extends TestCase
{
    public function test_position_description_create_page_renders_with_styles(): void
    {
        $admin = User::where('role', 'Administrator')->first();
        $this->assertNotNull($admin, 'no Administrator user seeded');

        $res = $this->actingAs($admin, 'web')->get('/position-descriptions/create');
        $res->assertOk();

        $html = $res->getContent();
        file_put_contents(sys_get_temp_dir().'/pd_create.html', $html);

        // The stylesheet is linked and cache-busted.
        $this->assertMatchesRegularExpression('/css\/hris-theme\.css\?v=\d+/', $html);

        // Every structural class the page depends on is present.
        foreach (['rec-page','rec-card','rec-grid','rec-field','rec-rows','rec-sticky','rec-total','rec-btn'] as $c) {
            $this->assertStringContainsString($c, $html, "missing .$c");
        }

        // All 23 sections are drawn.
        foreach (['1&ndash;3','4&ndash;8','9&ndash;14','15','16','17','18','19&ndash;20','21','22'] as $n) {
            $this->assertStringContainsString('<span class="n">'.$n.'</span>', $html, "missing section $n");
        }
    }
}
