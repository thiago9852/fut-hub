<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class TeamInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 20)]
    public ?string $shortName = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $slug = null;

    #[Assert\Length(max: 255)]
    public ?string $city = null;

    #[Assert\Length(max: 255)]
    #[Assert\Url(message: 'logo deve ser uma URL válida.')]
    public ?string $logo = null;

    #[Assert\Choice(choices: ['ATIVO', 'INATIVO'])]
    public string $status = 'ATIVO';
}
