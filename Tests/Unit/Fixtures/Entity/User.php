<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\UserBundle\Tests\Unit\Stub\UserStub as BaseUser;

class User extends BaseUser
{
    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
        parent::__construct();
        $this->id = $id;
        $this->salt = null;
    }
}
