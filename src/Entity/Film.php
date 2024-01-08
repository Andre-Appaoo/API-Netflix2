<?php

namespace App\Entity;

use App\Repository\FilmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FilmRepository::class)]
#[Groups(['getFilms'])]
class Film
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([
        'getActeurs', 'getActeur',
        'getFormats', 'getFormat',
        'getGenres', 'getGenre',
        'getLangues', 'getLangue',
        'getPlateformes', 'getPlateforme',
        'getRealisateurs', 'getRealisateur'
    ])]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Veuillez renseigner le titre du film")]
    #[Assert\Length(
        min: 1,
        max: 100,
        minMessage: "Le titre du film doit comporter au minimum {{ limit }} caractères",
        maxMessage: "Le titre du film doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getActeurs', 'getActeur',
        'getFormats', 'getFormat',
        'getGenres', 'getGenre',
        'getLangues', 'getLangue',
        'getPlateformes', 'getPlateforme',
        'getRealisateurs', 'getRealisateur'
    ])]
    private ?string $titre = null;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $synopsis = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner l'url de l'illustration du film")]
    #[Assert\Url(
        message: "L'url {{ value }} n'est pas une url valid",
        protocols: ['http', 'https']
    )]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "L'url de l'illustration du film doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'url de l'illustration du film doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getActeurs', 'getActeur',
        'getFormats', 'getFormat',
        'getGenres', 'getGenre',
        'getLangues', 'getLangue',
        'getPlateformes', 'getPlateforme',
        'getRealisateurs', 'getRealisateur'
    ])]
    private ?string $illustration = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 4, nullable: true)]
    #[Assert\Length(
        max: 4,
        maxMessage: "L'année doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $anneeSortie = null;

    /**
     * @var int|null
     */
    #[ORM\Column(nullable: true)]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private ?int $duree = null;

    /**
     * @var string|null
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez renseigner une url du film vers la plateforme")]
    #[Assert\Url(
        message: "L'url {{ value }} n'est pas une url valid",
        protocols: ['http', 'https']
    )]
    #[Assert\Length(
        min: 10,
        max: 255,
        minMessage: "L'url du film doit comporter au minimum {{ limit }} caractères",
        maxMessage: "L'url du film doit comporter au maximum {{ limit }} caractères"
    )]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateformes', 'getPlateforme',
        'getRealisateur'
    ])]
    private ?string $url = null;

    /**
     * @var Plateforme|null
     */
    #[ORM\ManyToOne(inversedBy: 'films')]
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Assert\NotBlank(message: "{{ value }} n'est pas un id de plateforme valide")]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getRealisateur'
    ])]
    private ?Plateforme $plateforme = null;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Acteur::class, inversedBy: 'films')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups([
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private Collection $acteurs;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Format::class, inversedBy: 'films')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups([
        'getActeur',
        'getGenre',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private Collection $formats;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'films')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups([
        'getActeur',
        'getFormat',
        'getLangue',
        'getPlateforme',
        'getRealisateur'
    ])]
    private Collection $genres;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Langue::class, inversedBy: 'films')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getPlateforme',
        'getRealisateur'
    ])]
    private Collection $langues;

    /**
     * @var Collection|ArrayCollection
     */
    #[ORM\ManyToMany(targetEntity: Realisateur::class, inversedBy: 'films')]
    #[ORM\JoinColumn(onDelete:"CASCADE")]
    #[Groups([
        'getActeur',
        'getFormat',
        'getGenre',
        'getLangue',
        'getPlateforme'
    ])]
    private Collection $realisateurs;

    /**
     *
     */
    public function __construct()
    {
        $this->acteurs = new ArrayCollection();
        $this->formats = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->langues = new ArrayCollection();
        $this->realisateurs = new ArrayCollection();
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
    public function getTitre(): ?string
    {
        return $this->titre;
    }

    /**
     * @param string $titre
     * @return $this
     */
    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSynopsis(): ?string
    {
        return $this->synopsis;
    }

    /**
     * @param string|null $synopsis
     * @return $this
     */
    public function setSynopsis(?string $synopsis): static
    {
        $this->synopsis = $synopsis;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    /**
     * @param string|null $illustration
     * @return $this
     */
    public function setIllustration(?string $illustration): static
    {
        $this->illustration = $illustration;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAnneeSortie(): ?string
    {
        return $this->anneeSortie;
    }

    /**
     * @param string|null $anneeSortie
     * @return $this
     */
    public function setAnneeSortie(?string $anneeSortie): static
    {
        $this->anneeSortie = $anneeSortie;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getDuree(): ?int
    {
        return $this->duree;
    }

    /**
     * @param int|null $duree
     * @return $this
     */
    public function setDuree(?int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return Plateforme|null
     */
    public function getPlateforme(): ?Plateforme
    {
        return $this->plateforme;
    }

    /**
     * @param Plateforme|null $plateforme
     * @return $this
     */
    public function setPlateforme(?Plateforme $plateforme): static
    {
        $this->plateforme = $plateforme;

        return $this;
    }

    /**
     * @return Collection<int, Acteur>
     */
    public function getActeurs(): Collection
    {
        return $this->acteurs;
    }

    /**
     * @param Acteur $acteur
     * @return $this
     */
    public function addActeur(Acteur $acteur): static
    {
        if (!$this->acteurs->contains($acteur)) {
            $this->acteurs->add($acteur);
        }

        return $this;
    }

    /**
     * @param Acteur $acteur
     * @return $this
     */
    public function removeActeur(Acteur $acteur): static
    {
        $this->acteurs->removeElement($acteur);

        return $this;
    }

    /**
     * @return Collection<int, Format>
     */
    public function getFormats(): Collection
    {
        return $this->formats;
    }

    /**
     * @param Format $format
     * @return $this
     */
    public function addFormat(Format $format): static
    {
        if (!$this->formats->contains($format)) {
            $this->formats->add($format);
        }

        return $this;
    }

    /**
     * @param Format $format
     * @return $this
     */
    public function removeFormat(Format $format): static
    {
        $this->formats->removeElement($format);

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    /**
     * @param Genre $genre
     * @return $this
     */
    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    /**
     * @param Genre $genre
     * @return $this
     */
    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    /**
     * @return Collection<int, Langue>
     */
    public function getLangues(): Collection
    {
        return $this->langues;
    }

    /**
     * @param Langue $langue
     * @return $this
     */
    public function addLangue(Langue $langue): static
    {
        if (!$this->langues->contains($langue)) {
            $this->langues->add($langue);
        }

        return $this;
    }

    /**
     * @param Langue $langue
     * @return $this
     */
    public function removeLangue(Langue $langue): static
    {
        $this->langues->removeElement($langue);

        return $this;
    }

    /**
     * @return Collection<int, Realisateur>
     */
    public function getRealisateurs(): Collection
    {
        return $this->realisateurs;
    }

    /**
     * @param Realisateur $realisateur
     * @return $this
     */
    public function addRealisateur(Realisateur $realisateur): static
    {
        if (!$this->realisateurs->contains($realisateur)) {
            $this->realisateurs->add($realisateur);
        }

        return $this;
    }

    /**
     * @param Realisateur $realisateur
     * @return $this
     */
    public function removeRealisateur(Realisateur $realisateur): static
    {
        $this->realisateurs->removeElement($realisateur);

        return $this;
    }
}
