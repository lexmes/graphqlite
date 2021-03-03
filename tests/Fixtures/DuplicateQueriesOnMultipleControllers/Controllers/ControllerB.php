<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Fixtures\DuplicateQueriesOnMultipleControllers\Controllers;

use TheCodingMachine\GraphQLite\Annotations\Query;

class ControllerB
{
    /**
     * @Query
     *
     * @return string[]
     */
    public function allPosts(): array
    {
        return [];
    }
}