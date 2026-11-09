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

        // The page is on the redesigned layout (Tailwind build), not the old
        // hris-theme.css .rec-* classes.
        $this->assertMatchesRegularExpression('/build\/assets\/[^"]+\.css/', $html);
        $this->assertStringContainsString('action="' . route('positionDescriptionStore'), $html);

        // Every numbered section of DBM-CSC Form No. 1 is drawn.
        foreach (['1&ndash;3','4&ndash;8','9&ndash;14','15','16','17','18','19&ndash;20','21','22'] as $n) {
            $this->assertMatchesRegularExpression('/<span class="[^"]*">' . preg_quote($n, '/') . '<\/span>/', $html, "missing section $n");
        }
    }
}
