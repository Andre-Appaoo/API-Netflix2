<?php

namespace App\Controller;

use App\Entity\Langue;
use App\Repository\FilmRepository;
use App\Repository\LangueRepository;
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

#[Route('/api/langues', name: 'api_')]
class LangueController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param LangueRepository $langueRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly LangueRepository $langueRepository
    )
    {
    }

    /**
     * @return JsonResponse
     */
    #[Route('', name: 'langues', methods: ['GET'])]
    public function getAllLangue(): JsonResponse
    {
        $allLangue = $this->langueRepository->findAll();

        return $this->json($allLangue, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getLangues']);
    }

    /**
     * @param Langue $langue
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'langue', methods: ['GET'])]
    public function getLangue(Langue $langue): JsonResponse
    {
        return $this->json($langue, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getLangue']);
    }

    /**
     * @param Langue $langue
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'langueDelete', methods: ['DELETE'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer une langue')]*/
    public function deleteLangue(Langue $langue): JsonResponse
    {
        $this->entityManager->remove($langue);
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
    #[Route('', name: 'langueCreate', methods: ['POST'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une langue')]*/
    public function createLangue(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $newLangue = $this->serializer->deserialize($request->getContent(), Langue::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newLangue, "addFilm");

        $errors = $validator->validate($newLangue);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($newLangue);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_langue', ['id' => $newLangue->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newLangue, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getLangue']);
    }

    /**
     * @param Request $request
     * @param Langue $langue
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'langueUpdate', methods: ['PUT'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une langue')]*/
    public function updateLangue(
        Request $request,
        Langue $langue,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $updateLangue = $this->serializer->deserialize($request->getContent(), Langue::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $langue]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updateLangue, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updateLangue);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($updateLangue);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
