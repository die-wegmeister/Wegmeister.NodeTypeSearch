<?php

declare(strict_types=1);

namespace Wegmeister\NodeTypeSearch\Command;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\ContentRepository\Domain\Service\ContextFactoryInterface;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;

/**
 * @Flow\Scope("singleton")
 */
class NodeTypeSearchCommandController extends CommandController
{
    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    /**
     * @var NodeInterface[]
     */
    protected $siteNodes = [];


    /**
     * Find URIs by nodeType
     *
     * @param string $nodeType The searched nodeType (like TBW.Site:Content.Code)
     * @param string $siteNodePath
     *
     * @return void
     */
    public function findUrisByNodeTypeCommand(
        string $nodeType,
        string $siteNodePath = '/sites',
        string $domain = '',
        bool $includeHidden = false
    ): void {
        $this->findUrisByFlowQueryFilterCommand('[instanceof ' . $nodeType . ']', $siteNodePath, $domain, $includeHidden);
    }


    /**
     * Find URIs by flowQueryFilter
     *
     * @param string $flowQueryFilter
     * @param string $siteNodePath
     *
     * @return void
     */
    public function findUrisByFlowQueryFilterCommand(
        string $flowQueryFilter,
        string $siteNodePath = '/sites',
        string $domain = '',
        bool $includeHidden = false
    ): void {
        $context = $this->contextFactory->create([
            'invisibleContentShown' => $includeHidden,
            'inaccessibleContentShown' => $includeHidden,
            // 'dimensions' => ['language' => [$language]],
            // 'targetDimensions' => ['language' => $language],
        ]);
        $siteNode = $context->getNode($siteNodePath);

        if (preg_match('/\[\!?instanceof /', $flowQueryFilter) === 0) {
            $flowQueryFilter = sprintf('[instanceof Neos.Neos:Content]%s', $flowQueryFilter);
        }

        $domain = rtrim($domain, '/');

        $flowQuery = new FlowQuery([$siteNode]);

        /** @var NodeInterface|TraversableNodeInterface[] $documentNodes */
        $documentNodes = $flowQuery
            // Find elements of the given nodetype
            ->find($flowQueryFilter)
            // Find closest documents
            ->closest('[instanceof Neos.Neos:Document]')
            // Return result as array
            ->get();

        $uris = [];

        foreach ($documentNodes as $documentNode) {
            $uri = '';
            /** @var NodeInterface|TraversableNodeInterface $documentNode */
            if ($documentNode->findNodePath()->getDepth() > 2) {
                $breadcrumbQuery = new FlowQuery([$documentNode]);
                /** @var NodeInterface|TraversableNodeInterface[] $parents */
                $parents = $breadcrumbQuery->parents('[instanceof Neos.Neos:Document]')->get();

                $uri = sprintf('/%s', $documentNode->getProperty('uriPathSegment'));

                $isHidden = $documentNode->isHidden();

                foreach ($parents as $parent) {
                    /** @var NodeInterface|TraversableNodeInterface $parent */
                    $isHidden = $isHidden || $parent->isHidden();
                    if ($parent->findNodePath()->getDepth() > 2) {
                        $uri = sprintf('/%s%s', $parent->getProperty('uriPathSegment'), $uri);
                    }
                }
            }

            $uri = sprintf('%s %s%s', $isHidden ? '🔴' : '🟢', $domain, $uri);

            $uris[] = $uri;
        }

        sort($uris);

        foreach ($uris as $uri) {
            $this->output->outputLine($uri);
        }
    }


    /**
     * Init the site nodes for each language
     *
     * @param string $siteNodePath The path to the parent node used for the import.
     *
     * @return void
     */
    protected function initLanguages(string $siteNodePath): void
    {
        foreach (['de', 'en'] as $language) {
            $context = $this->contextFactory->create([
                'dimensions' => ['language' => [$language]],
                // 'targetDimensions' => ['language' => $language],
            ]);
            $this->siteNodes[$language] = $context->getNode($siteNodePath);
        }
    }
}
