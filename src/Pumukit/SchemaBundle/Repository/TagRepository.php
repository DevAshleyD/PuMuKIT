<?php

namespace Pumukit\SchemaBundle\Repository;

use Gedmo\Tree\Document\MongoDB\Repository\MaterializedPathRepository;
use Pumukit\SchemaBundle\Document\Tag;

/**
 * TagRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TagRepository extends MaterializedPathRepository
{
    public function findOneByCod(string $cod)
    {
        return $this->findOneBy(['cod' => $cod]);
    }
}
