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

class ListController extends AbstractController
{
    public const DEFAULT_PAGE_SIZE = 120;
    private const DEFAULT_ENTITY = 'media';

    public function __construct(
        private AdminApi         $adminApi,
        private AdminRequest     $adminRequest,
        private SignatureService $signatureService
    )
    {
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $shop = $this->adminRequest->getShopFromRequest($request);
        $languageId = $request->query->get('sw-context-language');
        $result = $this->adminApi->list($shop, $languageId, self::DEFAULT_ENTITY);
        return DefaultController::render('List', [
            'result' => $result,
        ]);
    }

    #[Route('/list', name: 'search', methods: ['POST'])]
    public function search(Request $request): Response
    {
        return $this->fetch($request, 'List');
    }

    #[Route('/fetch', name: 'fetch', methods: ['GET'])]
    public function fetch(Request $request, string $templateName = 'Fetch', $pageSize = self::DEFAULT_PAGE_SIZE): Response
    {
        $shop = $this->adminRequest->getShopFromRequest($request);
        $languageId = $request->query->get('sw-context-language');
        $search = $request->get('search');
        $filter = [];
        if ($type = $request->get('type')) {
            if ($type === 'media') {
                $filter[] = ['type' => 'multi', 'operator' => 'or', 'queries' => [
                    ['type' => 'contains', 'field' => 'mimeType', 'value' => 'video'],
                    ['type' => 'contains', 'field' => 'mimeType', 'value' => 'audio'],
                ]];
            } elseif ($type === 'file') {
                $filter[] = ['type' => 'multi', 'operator' => 'or', 'queries' => [
                    ['type' => 'contains', 'field' => 'mimeType', 'value' => 'text'],
                    ['type' => 'contains', 'field' => 'mimeType', 'value' => 'application'],
                ]];
            } else {
                $filter[] = ['type' => 'contains', 'field' => 'mimeType', 'value' => $type];
            }
        }
        $page = $request->query->getInt('page', 1);

        $result = $this->adminApi->search($shop, $languageId, self::DEFAULT_ENTITY, [
            'total-count-mode' => $page === 1 ? 1 : 0,
            'page' => $page,
            'limit'=> $pageSize,
            'term' => $search,
            'filter' => $filter
        ]);

        return DefaultController::render($templateName, [
            'result' => $result,
            'search' => $search,
            'pageSize' => $pageSize,
            'listUrl' => $this->getFetchUrl($request, $shop, $page, $search)
        ]);
    }

    private function getFetchUrl(Request $request, ShopEntity $shop, int $page, ?string $search): UriInterface
    {
        $uri = $request->attributes->get(SignatureService::APP_SIGNATURE);
        if (!$uri instanceof Uri) {
            throw new \RuntimeException('Request is not verified.');
        }
        $queryUri = $uri->withPath($this->generateUrl('fetch'));
        $queryUri = Uri::withQueryValue($queryUri, 'search', $search);
        $queryUri = Uri::withQueryValue($queryUri, 'page', (string)($page + 1));

        return $this->signatureService->signQueryWithSignature(
            $queryUri, $shop?->getShopSecret()
        );
    }
}