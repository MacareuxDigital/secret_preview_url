<?php

namespace Concrete5cojp\SecretUrl\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index;

/**
 * @ORM\Entity(repositoryClass="SignatureRepository")
 * @ORM\Table(name="SecretUrlSignature", indexes={@Index(name="search_idx", columns={"signatureString"})})
 */
class Signature
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    protected $cID;

    /**
     * @var int
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $uID;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $previewDate;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $expirationDate;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    protected $signatureString;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCollectionID()
    {
        return $this->cID;
    }

    /**
     * @param int $cID
     */
    public function setCollectionID($cID)
    {
        $this->cID = $cID;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->uID;
    }

    /**
     * @param int $uID
     */
    public function setUserID($uID)
    {
        $this->uID = $uID;
    }

    /**
     * @return DateTime
     */
    public function getPreviewDate()
    {
        return $this->previewDate;
    }

    /**
     * @param DateTime $previewDate
     */
    public function setPreviewDate($previewDate)
    {
        $this->previewDate = $previewDate;
    }

    /**
     * @return DateTime
     */
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }

    /**
     * @param DateTime $expirationDate
     */
    public function setExpirationDate(DateTime $expirationDate)
    {
        $this->expirationDate = $expirationDate;
    }

    /**
     * @return string
     */
    public function getSignatureString()
    {
        return $this->signatureString;
    }

    /**
     * @param string $signatureString
     */
    public function setSignatureString($signatureString)
    {
        $this->signatureString = $signatureString;
    }
}