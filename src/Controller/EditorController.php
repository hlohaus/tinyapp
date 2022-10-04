<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\AdminRequest;
use App\Service\SignatureService;
use App\Service\AdminApi;
use App\Shop\ShopEntity;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class EditorController extends AbstractController
{
    private const DEFAULT_LANGUAGE = DefaultController::DEFAULT_LANGUAGE;

    public function __construct(
        private AdminApi         $adminApi,
        private AdminRequest     $adminRequest,
        private SignatureService $signatureService
    )
    {
    }

    #[Route('/open-in-modal', methods: ['POST'])]
    public function openEditorInModal(Request $request): Response
    {
        $resource = $this->adminRequest->getResourceFromRequest($request);
        return $this->signatureService->createSignedResponse([
            'actionType' => 'openModal',
            'payload' => [
                'iframeUrl' => str_replace(':8000', '', $this->generateUrl('editor', [
                    'entity' => $resource['entity'],
                    'entityId' => $resource['entityId'],
                ], UrlGeneratorInterface::ABSOLUTE_URL)),
                'size' => 'fullscreen',
                'expand' => true
            ]
        ], $resource['appSecret']);
    }

    #[Route('/open-in-new-tab', methods: ['POST'])]
    public function openEditorInNewTab(Request $request): Response
    {
        $resource = $this->adminRequest->getResourceFromRequest($request);
        return $this->signatureService->createSignedResponse([
            'actionType' => 'openNewTab',
            'payload' => [
                'redirectUrl' => str_replace(':8000', '', $this->generateUrl('editor', [
                    'entity' => $resource['entity'],
                    'entityId' => $resource['entityId'],
                ], UrlGeneratorInterface::ABSOLUTE_URL))
            ]
        ], $resource['appSecret']);
    }

    #[Route('/editor', name: 'editor', methods: ['GET'])]
    public function editor(Request $request): Response
    {
        $shop = $this->adminRequest->getShopFromRequest($request);
        $language = $request->query->get('sw-user-language', self::DEFAULT_LANGUAGE);
        $entity = [];

        if (isset($shop)) {
            $languageId = $request->query->get('sw-context-language');
            $entityName = $request->query->get('entity');
            if ($request->query->has('entityId')) {
                $entityId = $request->query->get('entityId');
                $entity = $this->adminApi->getEntity($shop, $languageId, $entityName, $entityId);
            }

            $form = [
                'language' => $language,
                'entityName' => $entityName,
                'entity' => $entity,
                'customFields' => $this->adminApi->getEditorCustomFields($shop, $languageId, $entityName),
            ];
        }

        return DefaultController::render('Editor', [
            'language' => $language,
            'options' => $request->query->get('options'),
            'entity' => $entity,
            'form' => $form ?? null,
            'shopUrl' => $shop?->getUrl() ?: $request->query->get('shop-url'),
            'sidebarUrl' => $this->getListUrl($request, $shop)
        ]);
    }

    #[Route('/editor', name: 'save', methods: ['POST'])]
    public function save(Request $request): Response
    {
        $this->adminRequest->updateEntityFromRequest($request);

        $uri = AdminRequest::getQueryFromRequest($request);

        $uri = AdminRequest::withQueryArray($uri, 'options', $request->request->get('options') ?? []);

        $uri = $this->signatureService->signQueryWithSignature(
            $uri,
            $request->attributes->get(SignatureService::APP_SECRET)
        );

        return $this->redirect((string)$uri);
    }

    private function getListUrl(Request $request, ShopEntity $shop): UriInterface
    {
        $uri = $request->attributes->get(SignatureService::APP_SIGNATURE);
        if (!$uri instanceof UriInterface) {
            throw new \RuntimeException('Request is not verified.');
        }
        $uri = Uri::withoutQueryValue($uri, 'entity');
        $uri = Uri::withoutQueryValue($uri, 'entityId');
        $uri = $uri->withPath($this->generateUrl('list'));

        return $this->signatureService->signQueryWithSignature($uri, $shop->getShopSecret());
    }
}