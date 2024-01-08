<?php

namespace App\Controller;

use App\Entity\Film;
use App\Repository\ActeurRepository;
use App\Repository\FilmRepository;
use App\Repository\FormatRepository;
use App\Repository\GenreRepository;
use App\Repository\LangueRepository;
use App\Repository\PlateformeRepository;
use App\Repository\RealisateurRepository;
use App\Service\RessourceService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[Route('/api/films', name: 'api_')]
class FilmController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param FilmRepository $filmRepository
     * @param PlateformeRepository $plateformeRepository
     * @param ActeurRepository $acteurRepository
     * @param FormatRepository $formatRepository
     * @param GenreRepository $genreRepository
     * @param LangueRepository $langueRepository
     * @param RealisateurRepository $realisateurRepository
     */
    public function __construct(
        private readonly SerializerInterface    $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly FilmRepository         $filmRepository,
        private readonly PlateformeRepository   $plateformeRepository,
        private readonly ActeurRepository       $acteurRepository,
        private readonly FormatRepository       $formatRepository,
        private readonly GenreRepository        $genreRepository,
        private readonly LangueRepository       $langueRepository,
        private readonly RealisateurRepository  $realisateurRepository
    )
    {
    }

    /**
     * @param Request $request
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'films', methods: ['GET'])]
    public function getAllFilm(Request $request, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $cacheId = "getAllFilm-$page-$limit";
        return $tagAwareCache->get(
            $cacheId,
            function (ItemInterface $item) use ($page, $limit)
            {
                $item->tag('filmsCache');
                $item->expiresAfter(600);

                return $this->json(
                    $this->filmRepository->findAllPaginated($page, $limit),
                    Response::HTTP_OK,
                    [],
                    [AbstractNormalizer::GROUPS => 'getFilms']
                );
            }
        );
    }

    /**
     * @param Film $film
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'film', methods: ['GET'])]
    public function getFilm(Film $film): JsonResponse
    {
        return $this->json($film, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getFilms']);
    }

    /**
     * @param Film $film
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'filmDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un film')]
    public function deleteFilm(Film $film, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $tagAwareCache->invalidateTags(['filmsCache']);
        $this->entityManager->remove($film);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'filmCreate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un film')]
    public function createFilm(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $newFilm = $this->serializer->deserialize($request->getContent(), Film::class, 'json');

        $content = $request->toArray();
        $acteurIds = $content["acteurIds"] ?? [];
        $formatIds = $content["formatIds"] ?? [];
        $genreIds = $content["genreIds"] ?? [];
        $langueIds = $content["langueIds"] ?? [];
        $realisateurIds = $content["realisateurIds"] ?? [];

        $newFilm->setPlateforme($this->plateformeRepository->find($content["plateformeId"]));
        RessourceService::addItemsInCollection($acteurIds, $this->acteurRepository, $newFilm, "addActeur");
        RessourceService::addItemsInCollection($formatIds, $this->formatRepository, $newFilm, "addFormat");
        RessourceService::addItemsInCollection($genreIds, $this->genreRepository, $newFilm, "addGenre");
        RessourceService::addItemsInCollection($langueIds, $this->langueRepository, $newFilm, "addLangue");
        RessourceService::addItemsInCollection($realisateurIds, $this->realisateurRepository, $newFilm, "addRealisateur");

        $errors = $validator->validate($newFilm);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['filmsCache']);
        $this->entityManager->persist($newFilm);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_film', ['id' => $newFilm->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newFilm, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getFilms']);
    }

    /**
     * @param Request $request
     * @param Film $film
     * @param ValidatorInterface $validator
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'acteurUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un film')]
    public function updateFilm(
        Request $request,
        Film $film,
        ValidatorInterface $validator,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $updateFilm = $this->serializer->deserialize($request->getContent(), Film::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $film]);

        $content = $request->toArray();
        $acteurIds = $content["acteurIds"] ?? [];
        $formatIds = $content["formatIds"] ?? [];
        $genreIds = $content["genreIds"] ?? [];
        $langueIds = $content["langueIds"] ?? [];
        $realisateurIds = $content["realisateurIds"] ?? [];

        $updateFilm->setPlateforme($this->plateformeRepository->find($content["plateformeId"]));

        RessourceService::putUpdateItemsInCollection($updateFilm, 'getActeurs', 'removeActeur', $acteurIds, $this->acteurRepository, 'addActeur');
        RessourceService::putUpdateItemsInCollection($updateFilm, 'getFormats', 'removeFormat', $formatIds, $this->formatRepository, 'addFormat');
        RessourceService::putUpdateItemsInCollection($updateFilm, 'getGenres', 'removeGenre', $genreIds, $this->genreRepository, 'addGenre');
        RessourceService::putUpdateItemsInCollection($updateFilm, 'getLangues', 'removeLangue', $langueIds, $this->langueRepository, 'addLangue');
        RessourceService::putUpdateItemsInCollection($updateFilm, 'getRealisateurs', 'removeRealisateur', $realisateurIds, $this->realisateurRepository, 'addRealisateur');

        $errors = $validator->validate($updateFilm);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['filmsCache']);
        $this->entityManager->persist($updateFilm);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}