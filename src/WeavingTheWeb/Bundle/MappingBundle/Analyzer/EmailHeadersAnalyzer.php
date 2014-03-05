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
     * @var \WeavingTheWeb\Bundle\MappingBundle\Parser\EmailHeadersParser $parser
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
    public function aggregateEmailHeadersProperties($options)
    {
        /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingHeaderRepository $headerRepository */
        $headerRepository = $this->entityManager->getRepository('WeavingTheWebLegacyProviderBundle:WeavingHeader');

        $memoryExceeded = false;
        $processedHeaders = 0;

        $emailHeadersProperties = array();
        while ($options['offset'] <= $options['max_offset']) {
            $headers = $headerRepository->paginate($options['offset'], $options['items_per_page'], $withoutSubject = true);

            /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Entity\WeavingHeader $header */
            foreach ($headers as $header) {
                $properties = $this->parser->parse($header->getHdrValue());
                if ($this->safeHeadersUpdate($header, $properties)) {
                    $processedHeaders++;
                }

                if ($options['save_headers_names']) {
                    foreach ($properties as $name => $value) {
                        $emailHeadersProperties[$name] = $value;
                    }
                    $this->saveEmailsHeadersAsProperties($emailHeadersProperties);
                    $this->logger->info(sprintf('%d headers have been parsed', count($emailHeadersProperties)));
                }

                $memoryPeakUsage = memory_get_peak_usage(true);
                if ($memoryPeakUsage > $options['memory_limit'] * 1024 * 1024) {
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

        if ($options['save_headers_names']) {
            $affectedItems = $emailHeadersProperties;
        } else {
            $affectedItems = $processedHeaders;
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
     * @param $emailHeadersProperties
     */
    protected function saveEmailsHeadersAsProperties($emailHeadersProperties)
    {
        /** @var \Doctrine\ORM\EntityRepository $propertyRepository */
        $propertyRepository = $this->entityManager->getRepository('WeavingTheWebMappingBundle:Property');
        foreach ($emailHeadersProperties as $name => $value) {
            $normalizedName = strtolower($name);
            $header = $propertyRepository->findOneBy(['name' => $normalizedName]);
            if (is_null($header)) {
                $property = new Property();
                $property->setName(strtolower($normalizedName));
                $property->setType($property::TYPE_EMAIL_HEADER);

                $this->entityManager->persist($property);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * @param WeavingHeader $header
     * @param $properties
     * @return bool
     */
    protected function safeHeadersUpdate(WeavingHeader $header, $properties)
    {
        $beforeHash = spl_object_hash($header);

        if (array_key_exists('from', $properties) && is_null($header->getFrom())) {
            $header->setFrom($properties['from']);
        }
        if (array_key_exists('subject', $properties) && is_null($header->getSubject())) {
            $header->setSubject($properties['subject']);
        }
        if (array_key_exists('to', $properties) && is_null($header->getTo())) {
            $header->setTo($properties['to']);
        }

        $this->entityManager->persist($header);
        $afterHash = spl_object_hash($header);

        return $beforeHash !== $afterHash;
    }
} 