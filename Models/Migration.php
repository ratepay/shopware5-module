<?php


namespace RpayRatePay\Models;


use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity(repositoryClass="RpayRatePay\Models\MigrationRepository")
 * @ORM\Table(name="rpay_ratepay_schema_version")
 */
class Migration extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\Column(type="integer")
     */
    private $version;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $startDate;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $completeDate;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $errorMsg;

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param DateTime $startDate
     */
    public function setStartDate(DateTime $startDate)
    {
        $this->startDate = $startDate;
    }

    /**
     * @return DateTime
     */
    public function getCompleteDate()
    {
        return $this->completeDate;
    }

    /**
     * @param DateTime $completeDate
     */
    public function setCompleteDate(DateTime $completeDate)
    {
        $this->completeDate = $completeDate;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * @param string $errorMsg
     */
    public function setErrorMsg($errorMsg)
    {
        $this->errorMsg = $errorMsg;
    }
}
