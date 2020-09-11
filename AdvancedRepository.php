<?php

namespace Skrip42\AdvancedRepository;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

abstract class AdvancedRepository extends ServiceEntityRepository
{
    private function getQueryByRelationParams(array $params)
    {
        $query = $this->createQueryBuilder('base');
        foreach ($params as $key => $value) {
            $parts = array_merge(
                ['base'],
                explode('.', $key)
            );
            $fullKey = implode('_', $parts);
            if (count($parts) == 2) {
                $query->andWhere($parts[0] . '.' . $parts[1] . ' = :' . $fullKey);
            } else {
                for ($i = 0; $i < count($parts) - 3; $i++) {
                    $query->innerJoin(
                        $parts[$i] . '.' . $parts[$i + 1],
                        $parts[$i + 1]
                    );
                }
                $query->innerJoin(
                    $parts[$i] . '.' . $parts[$i + 1],
                    $parts[$i + 1],
                    Expr\Join::WITH,
                    $parts[$i + 1] . '.' . $parts[$i + 2] . '= :' . $fullKey
                );
            }
            $query->setParameter($fullKey, $value);
        }
        return $query;
    }

    public function findByRelation(array $params, int $page, int $perPage)
    {
        $query = $this->getQueryByRelationParams($params);
        return $query->getQuery()->getResult();
    }

    public function paginateByRelation(array $params, int $page, int $perPage) : Pagerfanta
    {
        $query = $this->getQueryByRelationParams($params);
        return $this->createPaginator($query, $page, $perPage);
    }

    public function createPaginator(QueryBuilder $queryBuilder, int $page, int $maxPerPage)
    {
        $adapter = new QueryAdapter($queryBuilder);
        $paginator = new Pagerfanta($adapter);
        $paginator->setMaxPerPage($maxPerPage);
        $page = $page > $paginator->getNbPages() ? $paginator->getNbPages() : $page;
        $paginator->setCurrentPage($page);

        return $paginator;
    }
}
