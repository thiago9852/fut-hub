<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class PlayerInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $slug = null;

    #[Assert\Date]
    public ?string $birthDate = null;

    #[Assert\Length(max: 50)]
    public ?string $position = null;

    #[Assert\Choice(choices: ['ATIVO', 'INATIVO'])]
    public string $status = 'ATIVO';
}
