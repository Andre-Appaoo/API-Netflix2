<?php

namespace App\Service;

use Doctrine\ORM\EntityRepository;

class RessourceService
{
    /**
     * @param array $itemIds
     * @param EntityRepository $entityRepository
     * @param $newObject
     * @param string $methodName
     * @return void
     */
    public static function addItemsInCollection(array $itemIds, EntityRepository $entityRepository, $newObject, string $methodName): void
    {
        if (!empty($itemIds)) {
            foreach ($itemIds as $itemId) {
                $object = $entityRepository->find($itemId);
                if ($object) {
                    $newObject->$methodName($object);
                }
            }
        }
    }

    /**
     * @param $entity
     * @param string $methodNameGet
     * @param string $methodNameRemove
     * @return void
     */
    public static function removeAllItemsInCollection($entity, string $methodNameGet, string $methodNameRemove): void
    {
        foreach ($entity->$methodNameGet() as $item) {
            $entity->$methodNameRemove($item);
        }
    }

    /**
     * @param $updateObject
     * @param string $methodNameGet
     * @param string $methodNameRemove
     * @param array $itemIds
     * @param EntityRepository $entityRepository
     * @param string $methodName
     * @return void
     */
    public static function putUpdateItemsInCollection($updateObject, string $methodNameGet, string $methodNameRemove, array $itemIds, EntityRepository $entityRepository, string $methodName): void
    {
        self::removeAllItemsInCollection($updateObject, $methodNameGet, $methodNameRemove);
        self::addItemsInCollection($itemIds, $entityRepository, $updateObject, $methodName);
    }
}
