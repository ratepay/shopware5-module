<?php


namespace RpayRatePay\Models;


use Doctrine\ORM\EntityRepository;

class MigrationRepository extends EntityRepository
{

    public function findMigrationByNumber($number)
    {
        return $this->find($number);
    }


}
