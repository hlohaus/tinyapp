<?php declare(strict_types=1);

namespace App\Controller;

use App\Service\SignatureService;
use Shopware\AppBundle\Attribute\ConfirmationRoute;
use Shopware\AppBundle\Attribute\RegistrationRoute;
use Shopware\AppBundle\Shop\ShopRepositoryInterface;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\AppBundle\Registration\RegistrationService;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

class RegistrationController extends AbstractController
{
    public function __construct(
        private RegistrationService $registrationService,
        private HttpMessageFactoryInterface $psrHttpFactory,
        private ShopRepositoryInterface $shopRepository,
        private SignatureService $signatureService
    ) {
    }

    #[RegistrationRoute(name: 'shopware_app.register', path: '/register')]
    public function register(Request $request): JsonResponse
    {
        $proof = $this->registrationService->handleShopRegistrationRequest(
            $this->psrHttpFactory->createRequest($request),
            $this->generateUrl('shopware_app.confirm', [], RouterInterface::ABSOLUTE_URL)
        );

        return new JsonResponse($proof, Response::HTTP_OK);
    }

    #[ConfirmationRoute(name: 'shopware_app.confirm', path: '/confirm')]
    public function confirm(Request $request): JsonResponse
    {
        $this->registrationService->handleConfirmation(
            $this->psrHttpFactory->createRequest($request)
        );

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }


    #[Route('/delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $requestContent = json_decode($request->getContent(), true);

        $shop = $this->shopRepository->getShopFromId($requestContent['shopId']);

        $this->signatureService->verifyRequestContent($request, $shop);

        $this->shopRepository->deleteShop($shop);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}