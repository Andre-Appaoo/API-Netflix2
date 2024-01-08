<?php

namespace App\Controller;

use App\Entity\Plateforme;
use App\Repository\FilmRepository;
use App\Repository\PlateformeRepository;
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

#[Route('/api/plateformes', name: 'api_')]
class PlateformeController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param PlateformeRepository $plateformeRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly PlateformeRepository $plateformeRepository
    )
    {
    }

    /**
     * @param Request $request
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'plateformes', methods: ['GET'])]
    public function getAllPlateforme(Request $request, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $cacheId = "getAllPlateforme-$page-$limit";
        return $tagAwareCache->get(
            $cacheId,
            function (ItemInterface $item) use ($page, $limit)
            {
                $item->tag('plateformesCache');
                $item->expiresAfter(600);

                return $this->json(
                    $this->plateformeRepository->findAllPaginated($page, $limit),
                    Response::HTTP_OK,
                    [],
                    [AbstractNormalizer::GROUPS => 'getPlateformes']
                );
            }
        );
    }

    /**
     * @param Plateforme $plateforme
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'plateforme', methods: ['GET'])]
    public function getPlateforme(Plateforme $plateforme): JsonResponse
    {
        return $this->json($plateforme, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getPlateforme']);
    }

    /**
     * @param Plateforme $plateforme
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'plateformeDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer une plateforme')]
    public function deletePlateforme(Plateforme $plateforme, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $tagAwareCache->invalidateTags(['plateformesCache']);
        $this->entityManager->remove($plateforme);
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
    #[Route('', name: 'plateformeCreate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une plateforme')]
    public function createPlateforme(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $newPlateforme = $this->serializer->deserialize($request->getContent(), Plateforme::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newPlateforme, "addFilm");

        $errors = $validator->validate($newPlateforme);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['plateformesCache']);
        $this->entityManager->persist($newPlateforme);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_plateforme', ['id' => $newPlateforme->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newPlateforme, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getPlateforme']);
    }

    /**
     * @param Request $request
     * @param Plateforme $plateforme
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'plateformeUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une plateforme')]
    public function updatePlateforme(
        Request $request,
        Plateforme $plateforme,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
    ): JsonResponse
    {
        $updatePlateforme = $this->serializer->deserialize($request->getContent(), Plateforme::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $plateforme]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updatePlateforme, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updatePlateforme);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $tagAwareCache->invalidateTags(['plateformesCache']);
        $this->entityManager->persist($updatePlateforme);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
