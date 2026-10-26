<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeFaceVector;
use App\Services\FaceAIService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EAJFaceAITest extends TestCase
{
    use DatabaseTransactions;

    public function test_face_ai_service_health_check(): void
    {
        Http::fake([
            'http://127.0.0.1:8000/api/health/' => Http::response(['status' => 'ok'], 200),
        ]);

        $service = app(FaceAIService::class);
        $this->assertTrue($service->checkHealth());
    }

    public function test_face_ai_service_extract_embedding(): void
    {
        $fakeEmbedding = array_fill(0, 512, 0.05);

        Http::fake([
            'http://127.0.0.1:8000/api/register/' => Http::response([
                'status'    => 'success',
                'embedding' => $fakeEmbedding,
            ], 200),
        ]);

        $service = app(FaceAIService::class);
        $res = $service->extractEmbedding('data:image/jpeg;base64,fakedata');

        $this->assertSame('success', $res['status']);
        $this->assertCount(512, $res['embedding']);
    }

    public function test_employee_face_controller_register_face(): void
    {
        $employee = Employee::firstOrFail();
        $fakeEmbedding = array_fill(0, 512, 0.123);

        Http::fake([
            'http://127.0.0.1:8000/api/register/' => Http::response([
                'status'    => 'success',
                'embedding' => $fakeEmbedding,
            ], 200),
        ]);

        $response = $this->postJson(route('api.employee.registerFace'), [
            'employee_id' => $employee->id,
            'image'       => 'data:image/jpeg;base64,fakedata',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'             => true,
                'message'             => 'Master face vector registered successfully.',
                'employee_id'         => $employee->id,
                'embedding_dimension' => 512,
            ]);

        $vector = EmployeeFaceVector::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(512, $vector->embedding_dimension);
        $this->assertCount(512, $vector->master_embedding);
    }
}
