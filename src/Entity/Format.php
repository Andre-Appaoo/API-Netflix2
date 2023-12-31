<?php

namespace App\Entity;

use App\Repository\FormatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FormatRepository::class)]
#[Groups(['getFormats', 'getFormat'])]
class Format
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getFilms',
        'getActeur',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner l'intitulé du format")]
    #[Assert\Length(
        min: 2,
        max: 45,
        minMessage: "L'intitulé du format doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'intitulé du format doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getActeur',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $intitule = null;

    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'formats')]
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
            $film->addFormat($this);
        }

        return $this;
    }

    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            $film->removeFormat($this);
        }

        return $this;
    }
}
