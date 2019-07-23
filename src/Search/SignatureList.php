<?php
namespace Concrete5cojp\SecretPreviewUrl\Search;

use Concrete\Core\Search\ItemList\EntityItemList;
use Concrete\Core\Search\Pagination\PaginationProviderInterface;
use Concrete\Core\Support\Facade\Facade;
use Concrete5cojp\SecretPreviewUrl\Entity\Signature;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Pagerfanta\Adapter\DoctrineORMAdapter;

class SignatureList extends EntityItemList implements PaginationProviderInterface
{
    protected $itemsPerPage = 50;
    protected $autoSortColumns = ['s.expirationDate'];

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        $app = Facade::getFacadeApplication();

        return $app->make(EntityManagerInterface::class);
    }

    public function createQuery()
    {
        $this->query->select('s')->from(Signature::class, 's');
    }

    /**
     * @param $result
     *
     * @return Signature
     */
    public function getResult($result)
    {
        return $result;
    }

    /**
     * @return int|mixed
     */
    public function getTotalResults()
    {
        $count = 0;
        $query = $this->query->select('count(distinct s.id)')->setMaxResults(1)->resetDQLParts(['groupBy', 'orderBy']);
        try {
            $count = $query->getQuery()->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return $count;
    }

    /**
     * @return DoctrineORMAdapter
     */
    public function getPaginationAdapter()
    {
        return new DoctrineORMAdapter($this->deliverQueryObject());
    }
}
