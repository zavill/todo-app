<?php


namespace App\Command\GoldIntern;


use App\Entity\GoldInRequest;
use App\Service\GoldIntern\SendRequest;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Команда, для отправки запросов из базы на удаленный сервер
 *
 * Class SendRequestCommand
 * @package App\Command\GoldIntern
 */
class SendRequestCommand extends Command
{
    protected static $defaultName = 'goldin:send-data';

    private ObjectManager $entityManager;

    public function __construct(string $name = null, ContainerInterface $container)
    {
        parent::__construct($name);
        $this->entityManager = $container->get('doctrine')->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(
            [
                'Send Requests to Gold Intern Server',
                '===================================',
            ]
        );

        $requests = $this->entityManager->getRepository(GoldInRequest::class)->findBy(
            [],
            [],
            $_ENV['LIMIT_PER_REQUEST']
        );

        foreach ($requests as $request) {
            $id = $request->getId();

            $userId = $request->getUserId();
            $action = $request->getAction();

            $output->writeln(
                "Отправка на сервер запроса $id"
            );

            $result = SendRequest::run($userId, $action);

            if ($result === 404 || $result === 503) {
                $output->writeln(
                    [
                        "Ошибка при отправке запроса $result",
                        '===================================='
                    ]
                );
            } else {
                $output->writeln(
                    [
                        'Запрос успешно отправлен',
                        '========================'
                    ]
                );

                $this->entityManager->remove($request);
                $this->entityManager->flush();
            }
        }

        return Command::FAILURE;
    }
}