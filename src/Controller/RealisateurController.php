<?php

namespace App\Controller;

use App\Entity\Realisateur;
use App\Repository\FilmRepository;
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

#[Route('/api/realisateurs', name: 'api_')]
class RealisateurController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param RealisateurRepository $realisateurRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly RealisateurRepository $realisateurRepository
    )
    {
    }

    /**
     * @param Request $request
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'realisateurs', methods: ['GET'])]
    public function getAllRealisateur(Request $request, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $cacheId = "getAllRealisateur-$page-$limit";
        return $tagAwareCache->get(
            $cacheId,
            function (ItemInterface $item) use ($page, $limit)
            {
                $item->tag('realisateursCache');
                $item->expiresAfter(600);

                return $this->json(
                    $this->realisateurRepository->findAllPaginated($page, $limit),
                    Response::HTTP_OK,
                    [],
                    [AbstractNormalizer::GROUPS => 'getRealisateurs']
                );
            }
        );

    }

    /**
     * @param Realisateur $realisateur
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'realisateur', methods: ['GET'])]
    public function getRealisateur(Realisateur $realisateur): JsonResponse
    {
        return $this->json($realisateur, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getRealisateur']);
    }

    /**
     * @param Realisateur $realisateur
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'realisateurDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un réalisateur')]
    public function deleteRealisateur(Realisateur $realisateur, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $tagAwareCache->invalidateTags(['realisateursCache']);
        $this->entityManager->remove($realisateur);
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
    #[Route('', name: 'realisateurCreate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un réalisateur')]
    public function createRealisateur(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $newRealisateur = $this->serializer->deserialize($request->getContent(), Realisateur::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newRealisateur, "addFilm");

        $errors = $validator->validate($newRealisateur);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['realisateursCache']);
        $this->entityManager->persist($newRealisateur);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_realisateur', ['id' => $newRealisateur->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newRealisateur, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getRealisateur']);
    }

    /**
     * @param Request $request
     * @param Realisateur $realisateur
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'realisateurUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un réalisateur')]
    public function updateRealisateur(
        Request $request,
        Realisateur $realisateur,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $updateRealisateur = $this->serializer->deserialize($request->getContent(), Realisateur::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $realisateur]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updateRealisateur, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updateRealisateur);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['realisateursCache']);
        $this->entityManager->persist($updateRealisateur);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
