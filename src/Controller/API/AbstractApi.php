<?php


namespace App\Controller\API;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 *
 * Class AbstractApi
 * @package App\Controller\API
 */
abstract class AbstractApi extends AbstractController
{

    protected ?Request $request;

    protected ObjectManager $entityManager;

    /**
     * AbstractApi constructor.
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        RequestStack $requestStack,
        EntityManagerInterface $entityManager
    ) {
        $this->request = $requestStack->getCurrentRequest();

        $this->entityManager = $entityManager;
    }
}