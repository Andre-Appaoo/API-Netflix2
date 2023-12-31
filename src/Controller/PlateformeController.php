<?php

namespace App\Controller;

use App\Entity\Plateforme;
use App\Repository\FilmRepository;
use App\Repository\PlateformeRepository;
use App\Service\RessourceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @return JsonResponse
     */
    #[Route('', name: 'plateformes', methods: ['GET'])]
    public function getAllPlateforme(): JsonResponse
    {
        $allPlateforme = $this->plateformeRepository->findAll();

        return $this->json($allPlateforme, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getPlateformes']);
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
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'plateformeDelete', methods: ['DELETE'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer une plateforme')]*/
    public function deletePlateforme(Plateforme $plateforme): JsonResponse
    {
        $this->entityManager->remove($plateforme);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * @param Request $request
     * @param UrlGeneratorInterface $urlGenerator
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @return JsonResponse
     */
    #[Route('', name: 'plateformeCreate', methods: ['POST'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une plateforme')]*/
    public function createPlateforme(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
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
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'plateformeUpdate', methods: ['PUT'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une plateforme')]*/
    public function updatePlateforme(
        Request $request,
        Plateforme $plateforme,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
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

        $this->entityManager->persist($updatePlateforme);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
