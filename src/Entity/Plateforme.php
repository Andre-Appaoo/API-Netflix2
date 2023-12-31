<?php

namespace App\Entity;

use App\Repository\PlateformeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PlateformeRepository::class)]
#[Groups(['getPlateformes', 'getPlateforme'])]
class Plateforme
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getRealisateur'
    ])]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner l'intitulé de la plateforme")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "L'intitulé de la plateforme doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'intitulé de la plateforme doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getRealisateur'
    ])]
    private ?string $intitule = null;

    #[ORM\OneToMany(mappedBy: 'plateforme', targetEntity: Film::class)]
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
            $film->setPlateforme($this);
        }

        return $this;
    }

    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            // set the owning side to null (unless already changed)
            if ($film->getPlateforme() === $this) {
                $film->setPlateforme(null);
            }
        }

        return $this;
    }
}
