<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace EzSystems\HybridPlatformUi\EventSubscriber;

use EzSystems\HybridPlatformUi\Http\AdminRequestMatcher;
use EzSystems\HybridPlatformUi\Pjax\PjaxResponseMainContentMapper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Catches admin PJAX requests, and maps them to Hybrid views.
 */
class PjaxSubscriber implements EventSubscriberInterface
{
    /**
     * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
     */
    private $adminRequestMatcher;

    /**
     * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
     */
    private $pjaxRequestMatcher;

    /**
     * @var \EzSystems\HybridPlatformUi\Pjax\XpathPjaxResponseMainContentMapper
     */
    private $pjaxResponseMapper;

    public static function getSubscribedEvents()
    {
        return [KernelEvents::RESPONSE => ['mapPjaxResponseToMainContent', 10]];
    }

    public function __construct(
        PjaxResponseMainContentMapper $pjaxResponseMapper,
        AdminRequestMatcher $adminRequestMatcher,
        RequestMatcherInterface $pjaxRequestMatcher
    ) {
        $this->pjaxResponseMapper = $pjaxResponseMapper;
        $this->adminRequestMatcher = $adminRequestMatcher;
        $this->pjaxRequestMatcher = $pjaxRequestMatcher;
    }

    public function mapPjaxResponseToMainContent(FilterResponseEvent $event)
    {
        $request = $event->getRequest();

        if (
            $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST ||
            !$this->adminRequestMatcher->matches($request) ||
            !$this->pjaxRequestMatcher->matches($request)
        ) {
            return;
        }

        $response = $event->getResponse();

        // If AJAX update, follow the redirection and return the update for it.
        // If not an AJAX update, send the redirection.
        if ($response instanceof RedirectResponse) {
            $event->stopPropagation();

            return;
        }

        $this->pjaxResponseMapper->map($response);
    }
}
