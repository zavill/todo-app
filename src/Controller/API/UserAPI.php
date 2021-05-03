<?php


namespace App\Controller\API;


use App\Entity\User;
use App\Exception\ApiException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/users")
 * Class UserAPI
 * @package App\Controller\API
 */
class UserAPI extends AbstractApi
{
    protected UserRepository $userRepository;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
        parent::__construct($requestStack, $entityManager);
    }

    /**
     * @Route("/authorize", methods={"GET"})
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     */
    public function login(UserPasswordEncoderInterface $encoder): JsonResponse
    {
        try {
            $arData = $this->getAuthData();

            if (!$arData['user'] || !$encoder->isPasswordValid($arData['user'], $arData['plainPass'])) {
                throw new ApiException('Неверное имя пользователя или пароль', Response::HTTP_NOT_FOUND);
            }

            $token = new UsernamePasswordToken(
                $arData['user'],
                $arData['user']->getPassword(),
                $_ENV['APP_ENV'],
                $arData['user']->getRoles()
            );
            $this->get('security.token_storage')->setToken($token);

            return new JsonResponse(
                [
                    'data' =>
                        [
                            'username' => $arData['username']
                        ]
                ],
                Response::HTTP_OK
            );
        } catch (ApiException $exception) {
            return new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                $exception->getCode()
            );
        }
    }

    /**
     * @Route("/", methods={"POST"})
     */
    public function register(UserPasswordEncoderInterface $encoder): JsonResponse
    {
        try {
            $arData = $this->getAuthData();

            if ($arData['user']) {
                throw new ApiException('Пользователь с таким именем уже существует', Response::HTTP_UNAUTHORIZED);
            }

            $user = new User();
            $user->setUsername($arData['username']);
            $user->setPassword($encoder->encodePassword($user, $arData['plainPass']));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $token = new UsernamePasswordToken($user, $user->getPassword(), $_ENV['APP_ENV'], $user->getRoles());
            $this->get('security.token_storage')->setToken($token);

            return new JsonResponse(
                [
                    'data' =>
                        [
                            'username' => $arData['username']
                        ]
                ],
                Response::HTTP_OK
            );
        } catch (ApiException $exception) {
            return new JsonResponse(
                [
                    'error' => $exception->getMessage()
                ],
                $exception->getCode()
            );
        }
    }

    /**
     * @throws ApiException
     */
    public function getAuthData(): array
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new ApiException('Вы уже авторизованы', Response::HTTP_NOT_FOUND);
        }

        if (!$arData['username'] = $this->request->get('username')) {
            throw new ApiException('Не задано имя пользователя', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!$arData['plainPass'] = $this->request->get('password')) {
            throw new ApiException('Не задан пароль', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $arData['user'] = $this->userRepository->findOneBy(['username' => $arData['username']]);

        return $arData;
    }
}