<?php
namespace Concrete5cojp\SecretUrl\Entity;

use Concrete\Core\Entity\Express\EntityRepository;

class SignatureRepository extends EntityRepository
{
    public function findOneBySignature($signature)
    {
        return $this->findOneBy([
            'signatureString' => $signature
        ]);
    }
}