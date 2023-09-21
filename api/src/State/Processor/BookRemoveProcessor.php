<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Doctrine\Common\State\RemoveProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Book;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class BookRemoveProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: RemoveProcessor::class)]
        private ProcessorInterface $removeProcessor,
        #[Autowire(service: MercureProcessor::class)]
        private ProcessorInterface $mercureProcessor,
        private ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private IriConverterInterface $iriConverter
    ) {
    }

    /**
     * @param Book $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Book
    {
        $object = clone $data;

        // remove entity
        $this->removeProcessor->process($data, $operation, $uriVariables, $context);

        // publish on Mercure
        foreach (['/admin/books/{id}{._format}', '/books/{id}{._format}'] as $uriTemplate) {
            $iri = $this->iriConverter->getIriFromResource(
                $object,
                UrlGeneratorInterface::ABS_URL,
                $this->resourceMetadataCollectionFactory->create(Book::class)->getOperation($uriTemplate)
            );
            $this->mercureProcessor->process(
                $object,
                $operation,
                $uriVariables,
                $context + [
                    'item_uri_template' => $uriTemplate,
                    'data' => json_encode(['@id' => $iri]),
                ]
            );
        }

        return $data;
    }
}
