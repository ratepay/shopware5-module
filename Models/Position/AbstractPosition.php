<?php
/**
 * Copyright (c) 2020 Ratepay GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models\Position;


use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class AbstractPosition
{

    /**
     * @var integer
     * @ORM\Column(name="delivered", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $delivered = 0;
    /**
     * @var integer
     * @ORM\Column(name="cancelled", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $cancelled = 0;
    /**
     * @var integer
     * @ORM\Column(name="returned", type="integer", length=11, nullable=false, options={"default":0})
     */
    protected $returned = 0;

    /**
     * @return int
     */
    public function getReturned()
    {
        return $this->returned;
    }

    /**
     * @param int $returned
     */
    public function setReturned($returned)
    {
        $this->returned = $returned;
    }

    public function getOpenQuantity()
    {
        return $this->getOrderedQuantity() - $this->getDelivered() - $this->getCancelled();
    }

    /**
     * @return int
     */
    abstract function getOrderedQuantity();

    /**
     * @return int
     */
    public function getDelivered()
    {
        return $this->delivered;
    }

    /**
     * @param int $delivered
     */
    public function setDelivered($delivered)
    {
        $this->delivered = $delivered;
    }

    /**
     * @return int
     */
    public function getCancelled()
    {
        return $this->cancelled;
    }

    /**
     * @param int $cancelled
     */
    public function setCancelled($cancelled)
    {
        $this->cancelled = $cancelled;
    }

    /**
     * @return string|null
     */
    abstract public function getUniqueNumber();
}
