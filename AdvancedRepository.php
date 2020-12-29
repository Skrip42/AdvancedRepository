<?php
namespace Skrip42\AdvancedRepository;

use Pagerfanta\Pagerfanta;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

abstract class AdvancedRepository extends ServiceEntityRepository
{
    protected function buildQuery(array $params)
    {
        $joinList = [];
        $query = $this->createQueryBuilder('base');
        foreach ($params as $key => $value) {
            $valKey = $this->generateKey($key);
            $parts = $this->prepareParam($key, $valKey);
            $i = 0;
            for (; $i < count($parts) - 1; $i++) {
                $joinKey = $parts[$i][0] . ' ' . $parts[$i][1];
                if (count($parts[$i]) == 2) {
                    if (empty($joinList[$joinKey])) {
                        $query->innerJoin($parts[$i][0], $parts[$i][1]);
                    }
                } elseif (count($parts[$i]) === 3) {
                    if (empty($joinList[$joinKey])) {
                        $query->innerJoin(
                            $parts[$i][0],
                            $parts[$i][1],
                            Expr\Join::WITH,
                            $parts[$i][2]
                        );
                    } else {
                        $query->andWhere($parts[$i][2]);
                    }
                } else {
                    $cond = array_slice($parts[$i], 2);
                    $and = $query->expr()->andX(...$cond);
                    if (empty($joinList[$joinKey])) {
                        $query->innerJoin(
                            $parts[$i][0],
                            $parts[$i][1],
                            Expr\Join::WITH,
                            $and
                        );
                    } else {
                        $query->andWhere($and);
                    }
                }
                $joinList[$joinKey] = true;
            }
            if (is_null($value)) {
                $query->andWhere($parts[$i][0] . ' is NULL');
            } else if (is_array($value)) {
                $query->andWhere($parts[$i][0] . ' in (:' . $parts[$i][1] . ')');
                $query->setParameter($valKey, $value);
            } else if (is_object($value)) {
                $query->andWhere($parts[$i][0] . ' = :' . $parts[$i][1]);
                $query->setParameter($valKey, $value);
            } else {
                $value = explode('::', $value, 2);
                if (count($value) == 1) {
                    $query->andWhere($parts[$i][0] . ' = :' . $parts[$i][1]);
                    $query->setParameter($valKey, $value);
                } else {
                    $value[1] = preg_replace('~(?<!\\\\)\\\\(:)~', '\\1', $value[1]);
                    //die();
                    switch ($value[0]) {
                        case 'not':
                            $query->andWhere(
                                $parts[$i][0] . ' != :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, $value[1]);
                            break;
                        case 'like':
                            $query->andWhere(
                                $parts[$i][0] . ' like :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, '%' . $value[1] . '%');
                            break;
                        case 'notLike':
                            $query->andWhere(
                                $parts[$i][0] . ' like :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, '%' . $value[1] . '%');
                            break;
                        case 'in':
                            $query->andWhere(
                                $parts[$i][0] . ' in (:' . $parts[$i][1] . ')'
                            );
                            $query->setParameter($valKey, explode(',', $value[1]));
                            break;
                        case 'notIn':
                            $query->andWhere(
                                $parts[$i][0] . ' not in (:' . $parts[$i][1] . ')'
                            );
                            $query->setParameter($valKey, explode(',', $value[1]));
                            break;
                        case 'less':
                            $query->andWhere(
                                $parts[$i][0] . ' < :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, $value[1]);
                            break;
                        case 'lessOrEq':
                            $query->andWhere(
                                $parts[$i][0] . ' <= :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, $value[1]);
                            break;
                        case 'more':
                            $query->andWhere(
                                $parts[$i][0] . ' > :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, $value[1]);
                            break;
                        case 'moreOrEq':
                            $query->andWhere(
                                $parts[$i][0] . ' >= :' . $parts[$i][1]
                            );
                            $query->setParameter($valKey, $value[1]);
                            break;
                        case 'empty':
                            $query->andWhere($parts[$i][0] . ' IS NULL');
                            break;
                        case 'notEmpty':
                            $query->andWhere($parts[$i][0] . ' IS NOT NULL');
                            break;
                    }
                }
            }
        }
        return $query;
    }

    private function generateKey($params)
    {
        $params = str_replace('.', '_', $params);
        $params = str_replace(':', '_', $params);
        $params = str_replace('\\', '', $params);
        return $params;
    }

    private function prepareParam($params, $valKey)
    {
        if (empty($params)) {
            return [];
        }
        $chain = [];
        $parts = array_merge(
            ['base'],
            explode('.', $params)
        );
        $pp = $parts[0];
        $i = 1;
        for (; $i < count($parts) - 1; $i++) {
            $tmp = [];
            $sparts = explode(':', $parts[$i]);
            if (strpos($sparts[0], '\\') !== false) {
                $tmp[] = $sparts[0];
            } else {
                $tmp[] = $pp . '.' . $sparts[0];
            }
            $alias = str_replace('\\', '', $sparts[0]);
            $tmp[] = $alias;
            for ($j = 1; $j < count($sparts); $j += 2) {
                $tmp[] = $alias . '.' . $sparts[$j]
                    . ' = '  . $pp . '.' . $sparts[$j + 1];
            }
            $pp = $alias;
            $chain[] = $tmp;
        }
        $chain[] = [$pp . '.' . $parts[$i], $valKey];
        return $chain;
    }

    public function advancedFindBy(array $params)
    {
        $query = $this->buildQuery($params);
        return $query->getQuery()->getResult();
    }

    public function advancedPaginateBy(array $params, int $page, int $perPage) : Pagerfanta
    {
        $query = $this->buildQuery($params);
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
