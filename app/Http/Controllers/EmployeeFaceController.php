<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FaceAIService;
use App\Models\EmployeeFaceVector;
use Illuminate\Support\Facades\DB;
use Exception;

class EmployeeFaceController extends Controller
{
    protected FaceAIService $faceAI;

    public function __construct(FaceAIService $faceAI)
    {
        $this->faceAI = $faceAI;
    }

    public function registerFace(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'image'       => 'required|string', // Base64 encoded JPEG/PNG
        ]);

        try {
            // 1. Call EAJ-Face-AI Python engine
            $result = $this->faceAI->extractEmbedding($request->image);

            // Expecting response: { "status": "success", "embedding": [...512 floats...] }
            $embedding = $result['embedding']; 
            $dimension = count($embedding); // 512

            // 2. Transactionally update or create EmployeeFaceVector record
            $faceVector = DB::transaction(function () use ($request, $embedding, $dimension) {
                return EmployeeFaceVector::updateOrCreate(
                    ['employee_id' => $request->employee_id],
                    [
                        'master_embedding'    => $embedding, // Casts automatically to JSON in MySQL
                        'embedding_dimension' => $dimension, // 512
                    ]
                );
            });

            return response()->json([
                'success'             => true,
                'message'             => 'Master face vector registered successfully.',
                'employee_id'         => $faceVector->employee_id,
                'embedding_dimension' => $dimension,
            ]);

        } catch (Exception $e) {
            $statusCode = ($e->getCode() >= 400 && $e->getCode() < 600) ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], $statusCode);
        }
    }
}
