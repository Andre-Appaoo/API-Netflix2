<?php

namespace App\Controller;

use App\Entity\Format;
use App\Repository\FilmRepository;
use App\Repository\FormatRepository;
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

#[Route('/api/formats', name: 'api_')]
class FormatController extends AbstractController
{
    /**
     * @param SerializerInterface $serializer
     * @param EntityManagerInterface $entityManager
     * @param FormatRepository $formatRepository
     */
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormatRepository $formatRepository
    )
    {
    }

    /**
     * @return JsonResponse
     */
    #[Route('', name: 'formats', methods: ['GET'])]
    public function getAllFormat(): JsonResponse
    {
        $allFormat = $this->formatRepository->findAll();

        return $this->json($allFormat, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getFormats']);
    }

    /**
     * @param Format $format
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'format', methods: ['GET'])]
    public function getFormat(Format $format): JsonResponse
    {
        return $this->json($format, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'getFormat']);
    }

    /**
     * @param Format $format
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'formatDelete', methods: ['DELETE'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un format')]*/
    public function deleteFormat(Format $format): JsonResponse
    {
        $this->entityManager->remove($format);
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
    #[Route('', name: 'formatCreate', methods: ['POST'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un format')]*/
    public function createFormat(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $newFormat = $this->serializer->deserialize($request->getContent(), Format::class, 'json');

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::addItemsInCollection($filmIds, $filmRepository, $newFormat, "addFilm");

        $errors = $validator->validate($newFormat);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($newFormat);
        $this->entityManager->flush();

        $location = $urlGenerator->generate('api_format', ['id' => $newFormat->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return $this->json($newFormat, Response::HTTP_CREATED, ['Location' => $location], [AbstractNormalizer::GROUPS => 'getFormat']);
    }

    /**
     * @param Request $request
     * @param Format $format
     * @param ValidatorInterface $validator
     * @param FilmRepository $filmRepository
     * @return JsonResponse
     */
    #[Route('/{id}', name: 'formatUpdate', methods: ['PUT'])]
    /*#[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un format')]*/
    public function updateFormat(
        Request $request,
        Format $format,
        ValidatorInterface $validator,
        FilmRepository $filmRepository
    ): JsonResponse
    {
        $updateFormat = $this->serializer->deserialize($request->getContent(), Format::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $format]);

        $content = $request->toArray();

        $filmIds = $content["filmIds"] ?? [];

        RessourceService::putUpdateItemsInCollection($updateFormat, 'getFilms', 'removeFilm', $filmIds, $filmRepository, 'addFilm');

        $errors = $validator->validate($updateFormat);
        if ($errors->count() > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $this->entityManager->persist($updateFormat);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
