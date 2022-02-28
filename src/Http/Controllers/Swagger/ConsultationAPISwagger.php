<?php
namespace EscolaLms\Consultations\Http\Controllers\Swagger;

use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use Illuminate\Http\JsonResponse;

interface ConsultationAPISwagger
{
    /**
     * @OA\Post(
     *      path="/api/consultations/report-term/{orderItemId}",
     *      summary="Report term with bought consultation",
     *      tags={"Consultations"},
     *      description="Report term consultation",
     *      @OA\Parameter(
     *          name="orderItemId",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *      ),
     *      security={
     *          {"passport": {}},
     *      },
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(
     *                  property="term",
     *                  type="string",
     *                  example="2022-10-12 10:45",
     *              ),
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          ),
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json"
     *          )
     *      )
     * )
     */
    public function reportTerm(int $orderItemId, ReportTermConsultationRequest $request): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/approve-term/{consultationTermId}",
     *      description="Approve reported term with consultation author",
     *      @OA\Parameter(
     *          name="consultationTermId",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json"
     *          )
     *      )
     *   )
     */
    public function approveTerm(int $consultationTermId): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/reject-term/{consultationTermId}",
     *      description="Reject reported term with consultation author",
     *      @OA\Parameter(
     *          name="consultationTermId",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json"
     *          )
     *      )
     *   )
     */
    public function rejectTerm(int $consultationTermId): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/generate-jitsi/{consultationTermId}",
     *      description="Generate jitsi object for approved consultation term",
     *      @OA\Parameter(
     *          name="consultationTermId",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          ),
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  type="array",
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json"
     *          )
     *      )
     *   )
     */
    public function generateJitsi(int $consultationTermId): JsonResponse;
}
