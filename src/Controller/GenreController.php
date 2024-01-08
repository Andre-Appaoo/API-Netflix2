<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Repository\FilmRepository;
use App\Repository\GenreRepository;
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

#[Route('/api/genres', name: 'api_')]
class GenreController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param GenreRepository $genreRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly GenreRepository $genreRepository
    )
    {
    }

    /**
     * @param Request $request
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'genres', methods: ['GET'])]
    public function getAllGenre(Request $request, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $cacheId = "getAllGenre-$page-$limit";
        return $tagAwareCache->get(
            $cacheId,
            function (ItemInterface $item) use ($page, $limit)
            {
                $item->tag('genresCache');
                $item->expiresAfter(600);

                return $this->json(
                    $this->genreRepository->findAllPaginated($page, $limit),
                    Response::HTTP_OK,
                    [],
                    [AbstractNormalizer::GROUPS => 'getGenres']
                );
            }
        );
    }

    /**
     * @param Genre $genre
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'genre', methods: ['GET'])]
    public function getGenre(Genre $genre): JsonResponse
    {
        return $this->json($genre, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getGenre']);
    }

    /**
     * @param Genre $genre
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'genreDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un genre')]
    public function deleteGenre(Genre $genre, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $tagAwareCache->invalidateTags(['genresCache']);
        $this->entityManager->remove($genre);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'genreCreate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un genre')]
    public function createGenre(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $newGenre = $this->serializer->deserialize($request->getContent(), Genre::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newGenre, "addFilm");

        $errors = $validator->validate($newGenre);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['genresCache']);
        $this->entityManager->persist($newGenre);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_genre', ['id' => $newGenre->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newGenre, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getGenre']);
    }

    /**
     * @param Request $request
     * @param Genre $genre
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'genreUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un genre')]
    public function updateGenre(
        Request $request,
        Genre $genre,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $updateGenre = $this->serializer->deserialize($request->getContent(), Genre::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $genre]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updateGenre, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updateGenre);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['genresCache']);
        $this->entityManager->persist($updateGenre);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
