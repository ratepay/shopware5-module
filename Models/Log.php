<?php


namespace RpayRatePay\Models;


use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Entity()
 * @ORM\Table(name="rpay_ratepay_logging")
 */
class Log extends ModelEntity
{

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer", length=11, nullable=false)
     */
    protected $id;

    /**
     * @var \DateTime
     * @ORM\Column(name="date", type="datetime", nullable=false)
     */
    protected $date;

    /**
     * @var string
     * @ORM\Column(name="version", type="string", length=10, options={"default":"N/A"})
     */
    protected $version = 'N/A';
    /**
     * @var string
     * @ORM\Column(name="operation", type="string", length=255, options={"default":"N/A"})
     */
    protected $operation = 'N/A';

    /**
     * @var string
     * @ORM\Column(name="suboperation", type="string", length=255, options={"default":"N/A"})
     */
    protected $subOperation = 'N/A';

    /**
     * @var string
     * @ORM\Column(name="transactionId", type="string", length=255, options={"default":"N/A"})
     */
    protected $transationId = 'N/A';

    /**
     * @var string
     * @ORM\Column(name="firstname", type="string", length=255, options={"default":"N/A"})
     */
    protected $firstname = 'N/A';

    /**
     * @var string
     * @ORM\Column(name="lastname", type="string", length=255, options={"default":"N/A"})
     */
    protected $lastname = 'N/A';

    /**
     * @var string
     * @ORM\Column(name="request", type="text", nullable=false)
     */
    protected $request;

    /**
     * @var string
     * @ORM\Column(name="response", type="text", nullable=false)
     */
    protected $response;


    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * @param string $operation
     */
    public function setOperation($operation)
    {
        $this->operation = $operation;
    }

    /**
     * @return string
     */
    public function getSubOperation()
    {
        return $this->subOperation;
    }

    /**
     * @param string $subOperation
     */
    public function setSubOperation($subOperation)
    {
        $this->subOperation = $subOperation;
    }

    /**
     * @return string
     */
    public function getTransationId()
    {
        return $this->transationId;
    }

    /**
     * @param string $transationId
     */
    public function setTransationId($transationId)
    {
        $this->transationId = $transationId;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
