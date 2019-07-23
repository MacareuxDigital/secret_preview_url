<?php
namespace Concrete5cojp\SecretPreviewUrl\Entity;

use Carbon\Carbon;
use Concrete\Core\Entity\Express\EntityRepository;
use Doctrine\Common\Collections\Criteria;

class SignatureRepository extends EntityRepository
{
    public function findOneBySignature($signature)
    {
        return $this->findOneBy([
            'signatureString' => $signature,
        ]);
    }

    public function findExpiredEntities()
    {
        $criteria = Criteria::create()->where(
            Criteria::expr()->lt('expirationDate', Carbon::now())
        );

        return $this->matching($criteria);
    }
}
