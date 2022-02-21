<?php

namespace EscolaLms\Consultations\Dto;

use Carbon\Carbon;
use EscolaLms\Consultations\Dto\Contracts\ModelDtoContract;
use EscolaLms\Consultations\Models\Consultation;
use EscolaLms\Consultations\Models\ConsultationProposedTerm;
use Illuminate\Http\UploadedFile;

class ConsultationDto extends BaseDto implements ModelDtoContract
{
    protected string $name;
    protected string $status;
    protected string $description;
    protected ?string $activeTo;
    protected ?string $activeFrom;
    protected ?string $duration;
    protected ?int $basePrice;
    protected ?int $authorId;

    public function model(): Consultation
    {
        return Consultation::newModelInstance();
    }

    public function toArray($filters = false): array
    {
        $result = $this->fillInArray($this->model()->getFillable());
        return $filters ? array_filter($result) : $result;
    }

    protected function setProposedTerms(array $proposedTerms): void
    {
        $result = [];
        foreach ($proposedTerms as $term) {
            $result[] = new ConsultationProposedTerm(['proposed_at' => Carbon::make($term)]);
        }
        $this->relations['proposedTerms'] = $result;
    }

    public function setImage(UploadedFile $file)
    {
        $this->files['image_path'] = $file;
    }
}
