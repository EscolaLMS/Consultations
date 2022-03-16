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
    protected ?string $shortDesc;
    protected ?string $activeTo;
    protected ?string $activeFrom;
    protected ?string $duration;
    protected ?int $basePrice;
    protected ?int $authorId;
    protected ?string $imagePath;

    public function model(): Consultation
    {
        return Consultation::newModelInstance();
    }

    public function toArray($filters = false): array
    {
        $result = $this->fillInArray($this->model()->getFillable());
        return $filters ? array_filter($result) : $result;
    }

    public function getImagePath(): string
    {
        return $this->imagePath ?? '';
    }

    protected function setProposedTerms(array $proposedTerms): void
    {
        $result = [];
        foreach ($proposedTerms as $term) {
            if (is_int($term)) {
                $date = Carbon::parse($term/1000);
            } else {
                $date = Carbon::parse($term);
            }
            $result[] = new ConsultationProposedTerm(['proposed_at' => $date]);
        }
        $this->relations['proposedTerms'] = $result;
    }

    protected function setCategories(array $categories): void
    {
        $this->relations['categories'] = $categories;
    }

    protected function setImage(UploadedFile $file): void
    {
        $this->files['image_path'] = $file;
    }
}
