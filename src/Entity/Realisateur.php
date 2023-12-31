<?php

namespace App\Entity;

use App\Repository\RealisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RealisateurRepository::class)]
#[Groups(['getRealisateurs', 'getRealisateur'])]
class Realisateur
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
        'getPlateforme'
    ])]
    private ?int $id = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner le nom du réalisateur")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom du réalisateur doit comporter au minimum {{ limit }} caractères",
        maxMessage: "Le nom du réalisateur doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme'
    ])]
    private ?string $nom = null;

    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'realisateurs')]
    private Collection $films;

    public function __construct()
    {
        $this->films = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

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
            $film->addRealisateur($this);
        }

        return $this;
    }

    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            $film->removeRealisateur($this);
        }

        return $this;
    }
}
