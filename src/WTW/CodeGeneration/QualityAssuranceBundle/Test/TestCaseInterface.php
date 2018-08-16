<?php

namespace WTW\CodeGeneration\QualityAssuranceBundle\Test;

/**
 * @author Thierry Marianne <thierrym@weaving-the-web.org>
 */
interface TestCaseInterface
{
    public function createSchema();

    /**
     * @param  array $options
     * @param  array $server
     * @return mixed
     */
    public function getClient(array $options = array(), array $server = array());

    public function getContainer();

    /**
     * @param $name
     * @return mixed
     */
    public function getParameter($name);

    /**
     * @param $serviceId
     * @return mixed
     */
    public function getService($serviceId);

    public function requiredFixtures();
}
