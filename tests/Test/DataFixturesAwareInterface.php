<?php

namespace App\Test;

/**
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface DataFixturesAwareInterface
{
    public function requiredFixtures();

    public function requiredMySQLDatabase();
}
