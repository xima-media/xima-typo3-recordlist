<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Http\JsonResponse;

class CategoryTreeManipulation implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * Make sure it is the ajax request for fetching tree data
         *
         * @var Route $route
         */
        $route = $request->getAttribute('route');
        if ($route->getOption('_identifier') !== 'ajax_record_tree_data') {
            return $handler->handle($request);
        }

        $params = $request->getQueryParams();
        $overrideValues = json_decode($params['overrideValues'] ?? '[]', true, 512, JSON_THROW_ON_ERROR);
        $recordTypeValue = $params['recordTypeValue'] ?? '';
        $command = $params['command'] ?? '';

        // make sure it is the right request
        if ($recordTypeValue !== 'tx-ximatypo3recordlist-filter' || $command !== 'new') {
            return $handler->handle($request);
        }

        try {
            // manually check the selected categories from overrideValues
            $response = $handler->handle($request);
            $treeData = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            foreach ($treeData as &$treeItem) {
                if (!in_array((int)$treeItem['identifier'], $overrideValues, true)) {
                    // uncheck items that are not within $overrideValues (possibly set via TSconfig TCAdefaults for new records)
                    $treeItem['checked'] = false;
                    continue;
                }
                $treeItem['checked'] = true;
            }
            return new JsonResponse($treeData);
        } catch (\Exception) {
        }

        return $handler->handle($request);
    }
}
