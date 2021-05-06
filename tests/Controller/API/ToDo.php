<?php


namespace App\Tests\Controller\API;


use App\Repository\ToDoRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ToDo extends WebTestCase
{
    private string $baseURL = '/api/todoes/';

    /**
     * Тест на создание элемента
     */
    public function testPost()
    {
        $method = 'POST';
        $client = static::createClient();
        $user = static::$container->get(UserRepository::class)->find(4);

        /* Запрос без авторизации */
        $client->request($method, $this->baseURL);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $client->loginUser($user);

        /* Запрос с авторизацией, но без данных */
        $client->request($method, $this->baseURL);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        /* Правильный запрос */
        $client->request($method, $this->baseURL, ['name' => 'test']);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Тест на получение списка элементов
     */
    public function testGetList()
    {
        $method = 'GET';
        $client = static::createClient();
        $user = static::$container->get(UserRepository::class)->find(4);

        /* Запрос без авторизации */
        $client->request($method, $this->baseURL);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $client->loginUser($user);
        /* Запрос с авторизацией */
        $client->request($method, $this->baseURL);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Тест на получение одного элемента
     */
    public function testGetOne()
    {
        $method = 'GET';
        $client = static::createClient();
        $user = static::$container->get(UserRepository::class)->find(4);

        $toDoId = $this->getLastToDoElement($user->getId());
        $fakeToDoId = $this->getLastToDoElement($user->getId()-1);

        /* Запрос без авторизации */
        $client->request($method, "$this->baseURL$toDoId");
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $client->loginUser($user);

        /* Запрос с авторизацией, но с несуществующим todo-элементом*/
        $client->request($method, $this->baseURL.'1000000');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());

        /* Запрос с авторизацией, но с todo-элементов не принадлежащим пользователю*/
        $client->request($method, "$this->baseURL$fakeToDoId");
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        /* Корректный запрос */
        $client->request($method, "$this->baseURL$toDoId");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Тест на удаление элемента
     */
    public function testDeleteElement()
    {
        $method = 'DELETE';
        $client = static::createClient();
        $user = static::$container->get(UserRepository::class)->find(4);

        $toDoId = $this->getLastToDoElement($user->getId());
        $fakeToDoId = $this->getLastToDoElement($user->getId()-1);

        /* Запрос без авторизации */
        $client->request($method, "$this->baseURL$toDoId");
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        $client->loginUser($user);

        /* Запрос с авторизацией, но с элементом не принадлежащим пользователю */
        $client->request($method, "$this->baseURL$fakeToDoId");
        $this->assertEquals(401, $client->getResponse()->getStatusCode());

        /* Корректный запрос */
        $client->request($method, "$this->baseURL$toDoId");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * Получение ID последнего To-Do элемента пользователя
     *
     * @param int $userid
     * @return mixed
     */
    public function getLastToDoElement(int $userid)
    {
        $toDo = static::$container->get(ToDoRepository::class)->findOneBy(['User' => $userid], ['id' => 'DESC']);
        return $toDo->getId();
    }
}