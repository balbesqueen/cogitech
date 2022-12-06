<?php

namespace App\Command;

use App\Entity\Post;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:load-data',
    description: 'Loads data from JSONPlaceholder'
)]
class LoadDataCommand extends Command
{
    const BASE_URL = 'https://jsonplaceholder.typicode.com';
    const BATCH_SIZE = 25;

    private $em;

    public function __construct(ManagerRegistry $em)
    {
        parent::__construct();
        $this->em = $em->getManager();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $users = $this->getFormattedUsers(true);

        if (!$users) {
            $io->error('Error with loading users');
            return Command::FAILURE;
        }

        $posts = $this->getPosts();

        if (!$posts) {
            $io->error('Error with loading posts');
            return Command::FAILURE;
        }

        for ($i = 0; $i < count($posts); $i++) {
            $post = new Post;
            $post->setTitle($posts[$i]['title']);
            $post->setBody($posts[$i]['body']);
            $post->setAuthorName($users[$posts[$i]['userId']]);

            $this->em->persist($post);

            if (($i % self::BATCH_SIZE) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $io->success('Done');

        return Command::SUCCESS;
    }

    private function getPosts(): array
    {
        $response = HttpClient::create()->request('GET', self::BASE_URL . '/posts');

        if ($response->getStatusCode() === 200) {
            return $response->toArray();
        }

        return null;
    }

    private function getUsers(): array
    {
        $response = HttpClient::create()->request('GET', self::BASE_URL . '/users');

        if ($response->getStatusCode() === 200) {
            return $response->toArray();
        }

        return null;
    }

    private function getFormattedUsers(): array
    {
        $users = $this->getUsers();

        if (is_null($users)) {
            return $users;
        }

        $temp = [];

        for ($i = 0; $i < count($users); $i++) {
            $temp[$users[$i]['id']] = $users[$i]['name'];
        }

        return $temp;
    }
}
