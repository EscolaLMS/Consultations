<?php
namespace EscolaLms\Consultations\Http\Controllers\Swagger;

use EscolaLms\Consultations\Http\Requests\ListAPIConsultationsRequest;
use EscolaLms\Consultations\Http\Requests\ListConsultationsRequest;
use EscolaLms\Consultations\Http\Requests\ReportTermConsultationRequest;
use EscolaLms\Consultations\Http\Requests\ScheduleConsultationAPIRequest;
use EscolaLms\Consultations\Http\Requests\ShowAPIConsultationRequest;
use Illuminate\Http\JsonResponse;

interface ConsultationAPISwagger
{
    /**
     * @OA\Post(
     *      path="/api/consultations/report-term/{orderItemId}",
     *      security={
     *          {"passport": {}},
     *      },
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
     *      security={
     *          {"passport": {}},
     *      },
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
     *              mediaType="application/json"
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
     *                  @OA\Items(ref="#/components/schemas/ConsultationTerm")
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
    public function approveTerm(int $consultationTermId): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/reject-term/{consultationTermId}",
     *      security={
     *          {"passport": {}},
     *      },
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
     *              mediaType="application/json"
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
     *                  @OA\Items(ref="#/components/schemas/ConsultationTerm")
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
    public function rejectTerm(int $consultationTermId): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/generate-jitsi/{consultationTermId}",
     *      security={
     *          {"passport": {}},
     *      },
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

    /**
     * @OA\Get(
     *      path="/api/consultations",
     *      summary="Get a listing of the Consultations.",
     *      tags={"Consultations"},
     *      description="Get all Consultations",
     *      @OA\Parameter(
     *          name="order_by",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"finished_at", "started_at", "created_at", "name", "only_with_categories"}
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="order",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"ASC", "DESC"}
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="only_with_categories",
     *          description="Consultation has categories",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="boolean",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="page",
     *          description="Pagination Page Number",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *               default=1,
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          description="Pagination Per Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *               default=15,
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="name",
     *          description="Consultation name %LIKE%",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          description="Consultation status == ",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json"
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
     *                  @OA\Items(ref="#/components/schemas/Consultation")
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function index(ListAPIConsultationsRequest $listConsultationsRequest): JsonResponse;

    /**
     * @OA\Get(
     *      path="/api/consultations/me",
     *      security={
     *          {"passport": {}},
     *      },
     *      summary="Get a listing of the Consultations bought by user.",
     *      tags={"Consultations"},
     *      description="Get a listing of the Consultations bought by user",
     *      @OA\Parameter(
     *          name="order_by",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"finished_at", "started_at", "created_at", "name"}
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="order",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"ASC", "DESC"}
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="page",
     *          description="Pagination Page Number",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *               default=1,
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          description="Pagination Per Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="number",
     *               default=15,
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="name",
     *          description="Consultation name %LIKE%",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          description="Consultation status == ",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json"
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
     *                  @OA\Items(ref="#/components/schemas/ConsultationTermForUserCurrent")
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function forCurrentUser(ListConsultationsRequest $listConsultationsRequest): JsonResponse;

    /**
     * @OA\Get(
     *      tags={"Consultations"},
     *      path="/api/consultations/proposed-terms/{orderItemId}",
     *      security={
     *          {"passport": {}},
     *      },
     *      description="Get proposedTerm For OrderItem",
     *      @OA\Parameter(
     *          name="orderItemId",
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
    public function proposedTerms(int $orderItemId): JsonResponse;

    /**
     * @OA\Get(
     *      path="/api/consultations/{id}",
     *      summary="Display the specified Consultation",
     *      tags={"Consultations"},
     *      description="Get Consultation",
     *      @OA\Parameter(
     *          name="id",
     *          description="id of Consultation",
     *          @OA\Schema(
     *             type="integer",
     *         ),
     *          required=true,
     *          in="path"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json"
     *          ),
     *          @OA\Schema(
     *              type="object",
     *              @OA\Property(
     *                  property="success",
     *                  type="boolean"
     *              ),
     *              @OA\Property(
     *                  property="data",
     *                  ref="#/components/schemas/Consultation"
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function show(ShowAPIConsultationRequest $showAPIConsultationRequest, int $id): JsonResponse;

    /**
     * @OA\Get(
     *      path="/api/consultations/my-schedule",
     *      tags={"Consultations"},
     *      description="Get Consultation schedule for tutor",
     *      security={
     *          {"passport": {}},
     *      },
     *      @OA\Response(
     *          response=200,
     *          description="successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json"
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
     *                  @OA\Items(ref="#/components/schemas/ConsultationTerm")
     *              ),
     *              @OA\Property(
     *                  property="message",
     *                  type="string"
     *              )
     *          )
     *      )
     * )
     */
    public function schedule(ScheduleConsultationAPIRequest $scheduleConsultationAPIRequest): JsonResponse;
}
