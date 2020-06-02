<?php
/**
 * Copyright (c) 2020 RatePAY GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace RpayRatePay\Models;


use Doctrine\ORM\EntityRepository;

class MigrationRepository extends EntityRepository
{

    public function findMigrationByNumber($number)
    {
        return $this->find($number);
    }


}
