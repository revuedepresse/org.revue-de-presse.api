<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Analyzer;

use WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader,
    WeavingTheWeb\Bundle\MappingBundle\Entity\Property;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Analyzer
 */
class EmailHeadersAnalyzer 
{
    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    public $entityManager;

    /**
     * @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailParser $parser
     */
    public $parser;

    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $logger;

    /**
     * @param $options
     * @return array
     */
    public function analyze($options)
    {
        /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingHeaderRepository $headerRepository */
        $headerRepository = $this->entityManager->getRepository('WeavingTheWebLegacyProviderBundle:WeavingHeader');

        $memoryExceeded = false;
        $affectedItems = 0;

        while ($options['offset'] <= $options['max_offset']) {
            $headers = $headerRepository->paginate($options);

            /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader $header */
            foreach ($headers as $header) {
                $affectedItems = $this->aggregateHeaderProperties($header, $options, $affectedItems);

                if ($this->exceedingMemoryUsage($options)) {
                    $memoryExceeded = true;

                    break;
                }
            }

            $this->entityManager->flush();

            if ($memoryExceeded) {
                break;
            }

            $options['offset']++;
            $this->logger->info(sprintf('Moving selection cursor with offset set at %d', $options['offset']));
        }

        if ($memoryExceeded) {
            $this->entityManager->flush();
            $this->logger->info(sprintf(
                'Memory limit has been exceeded. Exiting now at offset %d with %d items per page',
                $options['offset'], $options['items_per_page']
            ));
        }

        return $affectedItems;
    }

    /**
     * @param $options
     * @return bool
     */
    protected function exceedingMemoryUsage($options)
    {
        $memoryPeakUsage = memory_get_peak_usage(true);

        return $memoryPeakUsage > $options['memory_limit'] * 1024 * 1024;
    }

    /**
     * @param WeavingHeader $header
     * @param $options
     * @param $affectedItems
     * @return mixed
     */
    protected function aggregateHeaderProperties(WeavingHeader $header, $options, $affectedItems)
    {
        if ($options['save_headers_names']) {
            $affectedItems = $affectedItems + $this->saveEmailsHeadersAsProperties($header);
            $this->logger->info(sprintf('%d headers have been parsed', $affectedItems));

            return $affectedItems;
        } elseif ($this->safeHeadersUpdate($header)) {
            $affectedItems++;

            return $affectedItems;
        }

        return $affectedItems;
    }

    /**
     * @param WeavingHeader $header
     * @return int
     */
    protected function saveEmailsHeadersAsProperties(WeavingHeader $header)
    {
        $affectedEmailsHeadersProperties = 0;

        /** @var \Doctrine\ORM\EntityRepository $propertyRepository */
        $propertyRepository = $this->entityManager->getRepository('WeavingTheWebMappingBundle:Property');
        $emailHeadersProperties = $this->parser->parseHeader($header->getHdrValue());
        foreach ($emailHeadersProperties as $name => $value) {
            $header = $propertyRepository->findOneBy(['name' => $name]);
            if (is_null($header)) {
                $property = new Property();
                $property->setName($name);
                $property->setType($property::TYPE_EMAIL_HEADER);

                $this->entityManager->persist($property);
                $affectedEmailsHeadersProperties++;
            }
        }
        $this->entityManager->flush();

        return $affectedEmailsHeadersProperties;
    }

    /**
     * @param WeavingHeader $header
     * @return bool
     */
    protected function safeHeadersUpdate(WeavingHeader $header)
    {
        $beforeHash = spl_object_hash($header);
        $parsedEmailHeadersProperties = $this->parser->parseHeader($header->getHdrValue());
        $targetHeadersProperties = ['from', 'to', 'subject'];
        foreach ($targetHeadersProperties as $headerProperty) {
            $getter = 'get' . ucfirst($headerProperty);
            $setter = 'set' . ucfirst($headerProperty);
            if (array_key_exists($headerProperty, $parsedEmailHeadersProperties) && is_null($header->$getter())) {
                $header->$setter($parsedEmailHeadersProperties[$headerProperty]);
            }
        }
        $this->entityManager->persist($header);
        $afterHash = spl_object_hash($header);

        return $beforeHash !== $afterHash;
    }
} 