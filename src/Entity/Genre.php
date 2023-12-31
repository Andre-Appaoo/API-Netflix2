<?php

namespace App\Entity;

use App\Repository\GenreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GenreRepository::class)]
#[Groups(['getGenres', 'getGenre'])]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?int $id = null;

    #[ORM\Column(length: 45, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner l'intitulé du genre")]
    #[Assert\Length(
        min: 2,
        max: 45,
        minMessage: "L'intitulé du genre doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'intitulé du genre doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getActeur',
        'getFormat',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $intitule = null;

    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'genres')]
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
            $film->addGenre($this);
        }

        return $this;
    }

    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            $film->removeGenre($this);
        }

        return $this;
    }
}
