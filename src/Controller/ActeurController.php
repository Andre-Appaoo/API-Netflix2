<?php

namespace App\Controller;

use App\Entity\Acteur;
use App\Repository\ActeurRepository;
use App\Repository\FilmRepository;
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

#[Route('/api/acteurs', name: 'api_')]
class ActeurController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param ActeurRepository $acteurRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActeurRepository $acteurRepository
    )
    {
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    #[Route('', name: 'acteurs', methods: ['GET'])]
    public function getAllActeur(Request $request): JsonResponse
    {
        $allActeur = $this->acteurRepository->findAllPaginated(
            $request->get('page', 1),
            $request->get('limit', 50)
        );
        return $this->json($allActeur, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getActeurs']);
    }

    /**
     * @param Acteur $acteur
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'acteur', methods: ['GET'])]
    public function getActeur(Acteur $acteur): JsonResponse
    {
        return $this->json($acteur, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getActeur']);
    }

    /**
     * @param Acteur $acteur
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'acteurDelete', methods: ['DELETE'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un acteur')]*/
    public function deleteActeur(Acteur $acteur): JsonResponse
    {
        $this->entityManager->remove($acteur);
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
    #[Route('', name: 'acteurCreate', methods: ['POST'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un acteur')]*/
    public function createActeur(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $newActeur = $this->serializer->deserialize($request->getContent(), Acteur::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newActeur, "addFilm");

        $errors = $validator->validate($newActeur);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($newActeur);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_acteur', ['id' => $newActeur->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newActeur, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getActeur']);
    }

    /**
     * @param Request $request
     * @param Acteur $acteur
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'acteurUpdate', methods: ['PUT'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un acteur')]*/
    public function updateActeur(
        Request $request,
        Acteur $acteur,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $updateActeur = $this->serializer->deserialize($request->getContent(), Acteur::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $acteur]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updateActeur, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updateActeur);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($updateActeur);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
