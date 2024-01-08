<?php

namespace App\Entity;

use App\Repository\ActeurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActeurRepository::class)]
#[Groups(['getActeurs', 'getActeur'])]
class Acteur
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getFilms',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner le nom de l'acteur")]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: "Le nom de l'acteur doit comporter au minimum {{ limit }} caractères",
        maxMessage: "Le nom de l'acteur doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getFilms',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $nom = null;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Film::class, mappedBy: 'acteurs')]
    private Collection $films;

    /**
     *
     */
    public function __construct()
    {
        $this->films = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getNom(): ?string
    {
        return $this->nom;
    }

    /**
     * @param string|null $nom
     * @return $this
     */
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

    /**
     * @param Film $film
     * @return $this
     */
    public function addFilm(Film $film): static
    {
        if (!$this->films->contains($film)) {
            $this->films->add($film);
            $film->addActeur($this);
        }

        return $this;
    }

    /**
     * @param Film $film
     * @return $this
     */
    public function removeFilm(Film $film): static
    {
        if ($this->films->removeElement($film)) {
            $film->removeActeur($this);
        }

        return $this;
    }
}
