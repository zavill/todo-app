<?php


namespace App\Controller\API;


use App\Entity\GoldInRequest;
use App\Entity\ToDo;
use App\Exception\ApiException;
use App\Repository\ToDoRepository;
use App\Service\GoldIntern\SendRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route("/todoes")
 *
 * Class ToDoAPI
 * @package App\Controller\API
 */
class ToDoAPI extends AbstractApi
{

    protected ToDoRepository $toDoRepository;

    protected UserInterface $user;

    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager,
        ToDoRepository $toDoRepository,
        Security $security
    ) {
        parent::__construct($requestStack, $entityManager);
        $this->toDoRepository = $toDoRepository;

        if ($security->getUser()) {
            $this->user = $security->getUser();
        }
    }

    /**
     * Получение ToDo по ID
     *
     * @Route("/{id}", methods={"GET"})
     */
    public function getOneById(int $id)
    {
        try {
            $this->checkAuthorization();
            $todo = $this->getToDo($id);

            return new JsonResponse(
                [
                    'data' => $todo->serializeJSON()
                ]
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
     * Создание ToDo
     *
     * @Route("/", methods={"POST"})
     */
    public function createToDo()
    {
        try {
            $this->checkAuthorization();

            if (!$name = $this->request->get('name')) {
                throw new ApiException(
                    'Не задано имя ToDo',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $todo = new ToDo();
            $todo->setName($name);
            $todo->setUser($this->user);

            $this->entityManager->persist($todo);
            $this->entityManager->flush();

            $this->sendRequest('created');

            return new JsonResponse(
                [
                    'data' => $todo->serializeJSON()
                ],
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
     * @Route("/{id}", methods={"PUT"})
     */
    public function updateToDo(int $id): JsonResponse
    {
        try {
            $this->checkAuthorization();
            $todo = $this->getToDo($id);

            if (!$name = $this->request->get('name')) {
                throw new ApiException(
                    'Не задано название ToDo',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            if (!(int)$sort = $this->request->get('sort')) {
                throw new ApiException(
                    'Не задана сортировка ToDo',
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            (bool)$isCompleted = $this->request->get('isCompleted');

            $todo->setName($name);
            $todo->setSort($sort);
            $todo->setIsCompleted($isCompleted);

            $this->entityManager->persist($todo);
            $this->entityManager->flush();

            $this->sendRequest('updated');

            return new JsonResponse(
                [
                    'data' => $todo->serializeJSON()
                ],
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
     * @Route("/{id}", methods={"DELETE"})
     */
    public function deleteToDo($id): JsonResponse
    {
        try {
            $this->checkAuthorization();
            $todo = $this->getToDo($id);

            $this->entityManager->remove($todo);
            $this->entityManager->flush();

            $this->sendRequest('deleted');

            return new JsonResponse(
                [
                    'data' => $id
                ]
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
     * @Route("/", methods={"GET"})
     */
    public function getList(): JsonResponse
    {
        try {
            $this->checkAuthorization();

            $sortField = $this->request->get('sortField');
            $sort = explode('|', $sortField);
            $orderBy = ($sortField ? [$sort[0] => $sort[1]] : []);

            $page = (int)$this->request->get('page') ?: 1;
            $limit = $_ENV['LIMIT_PER_PAGE'];

            $offset = ($page * $limit) - $limit;

            $rawResult = $this->toDoRepository->findBy(
                ['User' => $this->user->getId()],
                $orderBy,
                $limit,
                $offset
            );

            foreach ($rawResult as $toDo) {
                $normalizedResult[] = $toDo->serializeJSON();
            }

            if (!isset($normalizedResult)) {
                throw new ApiException(
                    'Не удалось получить список ToDo',
                    Response::HTTP_NOT_FOUND
                );
            }

            return new JsonResponse(
                [
                    'data' => $normalizedResult
                ]
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
     * Проверка на авторизацию пользователя
     *
     * @throws ApiException
     */
    public function checkAuthorization()
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new ApiException(
                'Вы не авторизованы',
                Response::HTTP_UNAUTHORIZED
            );
        }
    }

    /**
     * Получение ToDo с проверкой на принадлежность пользователю
     *
     * @throws ApiException
     */
    public function getToDo(int $id): ToDo
    {
        $todo = $this->toDoRepository->find($id);
        if (!$todo) {
            throw new ApiException(
                "ToDo с id $id не найдена",
                Response::HTTP_NOT_FOUND
            );
        }

        if ($todo->getUser()->getId() !== $this->user->getId()) {
            throw new ApiException(
                "ToDo с id $id не принадлежит вам",
                Response::HTTP_UNAUTHORIZED
            );
        }

        return $todo;
    }

    /**
     * Отправка запросов на сервер GoldIntern
     *
     * @param string $action
     * @throws ApiException
     */
    private function sendRequest(string $action)
    {
        $result = SendRequest::run($this->user->getId(), $action);

        if ($result === 404 || $result === 503) {
            $request = new GoldInRequest();
            $request->setUserId($this->user->getId());
            $request->setAction($action);

            $this->entityManager->persist($request);
            $this->entityManager->flush();

            throw new ApiException(
                    'Произошла ошибка подсистемы',
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }
}