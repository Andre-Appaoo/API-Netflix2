<?php

namespace App\Controller;

use App\Entity\Format;
use App\Repository\FilmRepository;
use App\Repository\FormatRepository;
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
     * @param Request $request
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('', name: 'formats', methods: ['GET'])]
    public function getAllFormat(Request $request, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 20);

        $cacheId = "getAllFormat-$page-$limit";
        return $tagAwareCache->get(
            $cacheId,
            function (ItemInterface $item) use ($page, $limit)
            {
                $item->tag('formatsCache');
                $item->expiresAfter(600);

                return $this->json(
                    $this->formatRepository->findAllPaginated($page, $limit),
                    Response::HTTP_OK,
                    [],
                    [AbstractNormalizer::GROUPS => 'getFormats']
                );
            }
        );
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
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'formatDelete', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour effacer un format')]
    public function deleteFormat(Format $format, TagAwareCacheInterface $tagAwareCache): JsonResponse
    {
        $tagAwareCache->invalidateTags(['formatsCache']);
        $this->entityManager->remove($format);
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
    #[Route('', name: 'formatCreate', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un format')]
    public function createFormat(
        Request $request,
        UrlGeneratorInterface $urlGenerator,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
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

        $tagAwareCache->invalidateTags(['formatsCache']);
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
     * @param TagAwareCacheInterface $tagAwareCache
     * @return JsonResponse
     * @throws InvalidArgumentException
     */
    #[Route('/{id}', name: 'formatUpdate', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un format')]
    public function updateFormat(
        Request $request,
        Format $format,
        ValidatorInterface $validator,
        FilmRepository $filmRepository,
        TagAwareCacheInterface $tagAwareCache
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

        $tagAwareCache->invalidateTags(['formatsCache']);
        $this->entityManager->persist($updateFormat);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
