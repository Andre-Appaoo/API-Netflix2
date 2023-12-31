<?php

namespace App\Entity;

use App\Repository\LangueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: LangueRepository::class)]
#[Groups(['getLangues', 'getLangue'])]
class Langue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner l'intitulé de la langue")]
    #[Assert\Length(
        min: 2,
        max: 45,
        minMessage: "L'intitulé de la langue doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'intitulé de la langue doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $intitule = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $code = null;

    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'langues')]
    private Collection $films;

    public function __construct()
    {
        $this->films = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(?string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection<int, Film>
     */
    public function getFilms(): Collection
    {
        return $this->films;
    }

    public function addFilm(Film $film): static
    {
        if (!$this->films->contains($film)) {
            $this->films->add($film);
            $film->addLangue($this);
        }

        return $this;
    }

    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            $film->removeLangue($this);
        }

        return $this;
    }
}
